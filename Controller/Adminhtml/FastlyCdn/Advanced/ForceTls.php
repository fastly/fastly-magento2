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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Advanced;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;

/**
 * Class ForceTls
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Advanced
 */
class ForceTls extends Action
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
     * ForceTls constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;

        parent::__construct($context);
    }

    /**
     * Upload VCL snippets
     *
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
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
            $clone = $this->api->cloneVersion($currActiveVersion);
            $checkIfSettingExists = $this->api->hasSnippet($activeVersion, Config::FORCE_TLS_SETTING_NAME);
            $snippets = $this->config->getVclSnippets(Config::FORCE_TLS_PATH);

            if (!$checkIfSettingExists) {
                // Add force TLS snippet
                foreach ($snippets as $key => $value) {
                    $snippetData = [
                        'name'      => Config::FASTLY_MAGENTO_MODULE . '_force_tls_' . $key,
                        'type'      => $key,
                        'dynamic'   => "0",
                        'priority'  => 10,
                        'content'   => $value
                    ];
                    $this->api->uploadSnippet($clone->number, $snippetData);
                }
            } else {
                // Remove force TLS snippet
                foreach ($snippets as $key => $value) {
                    $name = Config::FASTLY_MAGENTO_MODULE.'_force_tls_'.$key;
                    $this->api->removeSnippet($clone->number, $name);
                }
            }

            $this->api->validateServiceVersion($clone->number);

            if ($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }

            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                if ($checkIfSettingExists) {
                    $this->api->sendWebHook('*Force TLS has been turned OFF in Fastly version '. $clone->number . '*');
                } else {
                    $this->api->sendWebHook('*Force TLS has been turned ON in Fastly version '. $clone->number . '*');
                }
            }

            $comment = ['comment' => 'Magento Module turned ON Force TLS'];
            if ($checkIfSettingExists) {
                $comment = ['comment' => 'Magento Module turned OFF Force TLS'];
            }
            $this->api->addComment($clone->number, $comment);

            return $result->setData(['status' => true]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
