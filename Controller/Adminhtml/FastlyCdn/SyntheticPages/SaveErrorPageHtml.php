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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\SyntheticPages;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;

/**
 * Class SaveErrorPageHtml
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\SyntheticPages
 */
class SaveErrorPageHtml extends Action
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
     * SaveErrorPageHtml constructor.
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
     * Save Error Page Html
     *
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $activateVcl = $this->getRequest()->getParam('activate_flag');
            $html = $this->getRequest()->getParam('html');
            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $clone = $this->api->cloneVersion($currActiveVersion);
            $snippets = $this->config->getVclSnippets(
                Config::VCL_ERROR_SNIPPET_PATH,
                Config::VCL_ERROR_SNIPPET
            );

            foreach ($snippets as $key => $value) {
                $snippetData = [
                    'name'      => Config::FASTLY_MAGENTO_MODULE . '_error_page_' . $key,
                    'type'      => $key,
                    'dynamic'   => '0',
                    'content'   => $value
                ];
                $this->api->uploadSnippet($clone->number, $snippetData);
            }

            $condition = [
                'name' => Config::FASTLY_MAGENTO_MODULE.'_error_page_condition',
                'statement' => 'req.http.ResponseObject == "970"',
                'type' => 'REQUEST',
                'priority' => '9'
            ];

            $createCondition = $this->api->createCondition($clone->number, $condition);
            $response = [
                'name'              => Config::ERROR_PAGE_RESPONSE_OBJECT,
                'request_condition' => $createCondition->name,
                'content'           =>  $html,
                'status'            => "503",
                'content_type'      => "text/html; charset=utf-8",
                'response'          => "Service Temporarily Unavailable"
            ];

            $createResponse = $this->api->createResponse($clone->number, $response);

            if (!$createResponse) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to create a RESPONSE object.'
                ]);
            }

            $this->api->validateServiceVersion($clone->number);

            if ($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }

            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                $this->api->sendWebHook(
                    '*New Error/Maintenance page has updated and activated under config version ' . $clone->number . '*'
                );
            }

            $comment = ['comment' => 'Magento Module updated Error Page html'];
            $this->api->addComment($clone->number, $comment);

            return $result->setData([
                'status'            => true,
                'active_version'    => $clone->number
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
