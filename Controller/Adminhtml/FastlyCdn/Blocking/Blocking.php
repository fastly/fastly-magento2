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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Blocking;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Blocking
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Blocking
 */
class Blocking extends AbstractBlocking
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
     * Blocking constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     * @param ConfigWriter $configWriter
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl,
        ConfigWriter $configWriter
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
        parent::__construct($context, $configWriter);
    }

    /**
     * Upload Blocking snippets
     *
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $activateVcl = $this->getRequest()->getParam('activate_flag');
            $blockingType = $this->getRequest()->getParam('blocking_type');
            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $clone = $this->api->cloneVersion($currActiveVersion);
            $checkIfSettingExists = $this->api->hasSnippet($activeVersion, Config::BLOCKING_SETTING_NAME);

            $snippet = $this->config->getVclSnippets(
                Config::VCL_BLOCKING_PATH,
                Config::VCL_BLOCKING_SNIPPET
            );

            $countryCodes = $this->getParamArray('countries');
            $this->storeConfigArray(Config::XML_FASTLY_BLOCK_BY_COUNTRY, $countryCodes);

            $acls = $this->getParamArray('acls');
            $this->storeConfigArray(Config::XML_FASTLY_BLOCK_BY_ACL, $acls);

            $blockedItems = $this->prepareBlockedItems($countryCodes, $acls, (int) $blockingType);

            $this->configWriter->save(
                Config::XML_FASTLY_BLOCKING_TYPE,
                $blockingType,
                'default',
                '0'
            );

            if (!$checkIfSettingExists) {
                foreach ($snippet as $key => $value) {
                    $name = Config::FASTLY_MAGENTO_MODULE . '_blocking_' . $key;

                    if ($this->api->hasSnippet($clone->number, $name) == true) {
                        $this->api->removeSnippet($clone->number, $name);
                        continue;
                    }

                    $value = $blockedItems !== '' ?
                        str_replace('####BLOCKED_ITEMS####', $blockedItems, $value) :
                        '';

                    $snippetData = [
                        'name'      => $name,
                        'type'      => $key,
                        'dynamic'   => 1,
                        'priority'  => 5,
                        'content'   => $value
                    ];

                    $this->api->uploadSnippet($clone->number, $snippetData);
                }
            } else {
                foreach ($snippet as $key => $value) {
                    $name = Config::FASTLY_MAGENTO_MODULE . '_blocking_' . $key;

                    if ($this->api->hasSnippet($clone->number, $name) == true) {
                        $this->api->removeSnippet($clone->number, $name);
                    }
                }
            }

            $this->api->validateServiceVersion($clone->number);

            if ($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }

            $this->sendWebhook($checkIfSettingExists, $clone);

            $comment = ['comment' => 'Magento Module turned ON Blocking'];
            if ($checkIfSettingExists) {
                $comment = ['comment' => 'Magento Module turned OFF Blocking'];
            }
            $this->api->addComment($clone->number, $comment);

            return $result->setData([
                'status' => true
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }

    private function sendWebhook($checkIfSettingExists, $clone)
    {
        if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
            if ($checkIfSettingExists) {
                $this->api->sendWebHook('*Blocking has been turned OFF in Fastly version ' . $clone->number . '*');
            } else {
                $this->api->sendWebHook('*Blocking has been turned ON in Fastly version ' . $clone->number . '*');
            }
        }
    }
}
