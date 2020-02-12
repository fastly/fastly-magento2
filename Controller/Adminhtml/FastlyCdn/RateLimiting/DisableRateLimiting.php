<?php
/**
 * Fastly CDN for Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Fastly CDN for Magento End User License Agreement
 * that is bundled with this package in the file LICENSE_FASTLY_CDN.txt.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Fastly CDN to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Fastly
 * @package     Fastly_Cdn
 * @copyright   Copyright (c) 2016 Fastly, Inc. (http://www.fastly.com)
 * @license     BSD, see LICENSE_FASTLY_CDN.txt
 */
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\RateLimiting;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeList;
use Magento\Config\App\Config\Type\System as SystemConfig;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class DisableRateLimiting
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\RateLimiting
 */
class DisableRateLimiting extends Action
{
    /**
     * @var Http
     */
    private $request;
    /**
     * @var JsonFactory
     */
    private $resultJson;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Vcl
     */
    private $vcl;
    /**
     * @var ConfigWriter
     */
    private $configWriter;
    /**
     * @var CacheTypeList
     */
    private $cacheTypeList;
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * ToggleRateLimiting constructor.
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     * @param ConfigWriter $configWriter
     * @param CacheTypeList $cacheTypeList
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl,
        ConfigWriter $configWriter,
        CacheTypeList $cacheTypeList,
        SystemConfig $systemConfig
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->systemConfig = $systemConfig;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $activateVcl = $this->getRequest()->getParam('activate_flag');
            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $reqName = Config::RATE_LIMITING_SETTING_NAME;
            $checkIfReqExist = $this->api->getRequest($activeVersion, $reqName);

            $snippet = $this->config->getVclSnippets(
                Config::VCL_RATE_LIMITING_PATH,
                Config::VCL_RATE_LIMITING_SNIPPET
            );

            if ($checkIfReqExist) {
                $clone = $this->api->cloneVersion($currActiveVersion);
                $this->api->deleteRequest($clone->number, $reqName);

                foreach ($snippet as $key => $value) {
                    $name = Config::FASTLY_MAGENTO_MODULE . '_rate_limiting_' . $key;
                    if ($this->api->hasSnippet($clone->number, $name) == true) {
                        $this->api->removeSnippet($clone->number, $name);
                    }
                }

                $this->api->validateServiceVersion($clone->number);

                if ($activateVcl === 'true') {
                    $this->api->activateVersion($clone->number);
                }

                $activeVersion = $clone->number;
                $this->sendWebHook($checkIfReqExist, $clone);

                if ($checkIfReqExist) {
                    $comment = ['comment' => 'Magento Module turned OFF Path Protection'];
                    $this->api->addComment($clone->number, $comment);
                }
            }

            $this->configWriter->save(
                Config::XML_FASTLY_RATE_LIMITING_MASTER_ENABLE,
                0,
                'default',
                '0'
            );

            $this->configWriter->save(
                Config::XML_FASTLY_RATE_LIMITING_LOGGING_ENABLE,
                0,
                'default',
                '0'
            );

            $this->configWriter->save(
                Config::XML_FASTLY_RATE_LIMITING_ENABLE,
                0,
                'default',
                '0'
            );

            $this->configWriter->save(
                Config::XML_FASTLY_CRAWLER_PROTECTION_ENABLE,
                0,
                'default',
                '0'
            );

            $this->cacheTypeList->cleanType('config');
            $this->systemConfig->clean();

            return $result->setData([
                'status'            => true,
                'active_version'    => $activeVersion
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }

    /**
     * @param $checkIfReqExist
     * @param $clone
     */
    private function sendWebHook($checkIfReqExist, $clone)
    {
        if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
            if ($checkIfReqExist) {
                $this->api->sendWebHook('*Path Protection has been turned OFF in Fastly version '
                    . $clone->number . '*');
            } else {
                $this->api->sendWebHook('*Path Protection has been turned ON in Fastly version '
                    . $clone->number . '*');
            }
        }
    }
}
