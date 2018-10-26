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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\BasicAuthentication;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class EnableAuth
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\BasicAuthentication
 */
class EnableAuth extends Action
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
     * Upload Auth VCL snippets
     *
     * @return $this|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $activateVcl = $this->getRequest()->getParam('activate_flag');
            $service = $this->api->checkServiceDetails();
            $enabled = false;
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $vclPath = Config::VCL_AUTH_SNIPPET_PATH;
            $snippets = $this->config->getVclSnippets($vclPath);

            // Check if snippets exist
            $status = true;
            foreach ($snippets as $key => $value) {
                $name = Config::FASTLY_MAGENTO_MODULE.'_basic_auth_'.$key;
                $status = $this->api->getSnippet($activeVersion, $name);

                if (!$status) {
                    break;
                }
            }

            if (!$status) {
                // Check if Auth has entries
                $this->api->checkAuthDictionaryPopulation($activeVersion);
                $clone = $this->api->cloneVersion($currActiveVersion);

                // Insert snippet
                foreach ($snippets as $key => $value) {
                    $snippetData = [
                        'name'      => Config::FASTLY_MAGENTO_MODULE.'_basic_auth_'.$key,
                        'type'      => $key,
                        'dynamic'   => "0",
                        'content'   => $value,
                        'priority'  => 10
                    ];
                    $this->api->uploadSnippet($clone->number, $snippetData);
                }

                $enabled = true;
            } else {
                $clone = $this->api->cloneVersion($currActiveVersion);

                // Remove snippets
                foreach ($snippets as $key => $value) {
                    $name = Config::FASTLY_MAGENTO_MODULE.'_basic_auth_'.$key;
                    $this->api->removeSnippet($clone->number, $name);
                }
            }

            $this->api->validateServiceVersion($clone->number);

            if ($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }

            $this->sendWebhook($enabled, $clone);

            $comment = ['comment' => 'Magento Module turned ON Basic Authentication'];
            if (!$enabled) {
                $comment = ['comment' => 'Magento Module turned OFF Basic Authentication'];
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

    private function sendWebhook($enabled, $clone)
    {
        if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
            if (!$enabled) {
                $this->api->sendWebHook(
                    '*Basic Authentication has been turned OFF in Fastly version ' . $clone->number . '*'
                );
            } else {
                $this->api->sendWebHook(
                    '*Basic Authentication has been turned ON in Fastly version '. $clone->number . '*'
                );
            }
        }
    }
}
