<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Manifest;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Fastly\Cdn\Model\Config\Backend\CustomSnippetUpload;
use Fastly\Cdn\Model\Modly\Manifest;
use Magento\Framework\Exception\LocalizedException;

class Upload extends Action
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
     * @var \Fastly\Cdn\Model\Api
     */
    private $api;

    /**
     * @var Vcl
     */
    private $vcl;

    private $manifest;

    /**
     * @var CustomSnippetUpload
     */
    private $customSnippetUpload;

    /**
     * Upload constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     * @param CustomSnippetUpload $customSnippetUpload
     * @param Manifest $manifest
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl,
        CustomSnippetUpload $customSnippetUpload,
        manifest $manifest
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
        $this->customSnippetUpload = $customSnippetUpload;
        $this->manifest = $manifest;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $moduleId = $this->getRequest()->getParam('module_id');
            $snippets = json_decode($this->getRequest()->getParam('snippets'));

            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $clone = $this->api->cloneVersion($currActiveVersion);

            foreach ($snippets as $key => $value) {
                $snippetData = [
                    'name'      => Config::FASTLY_MODLY_MODULE . '_' . $moduleId . '_' . $value->type,
                    'type'      => $value->type,
                    'dynamic'   => "0",
                    'priority'  => 50,
                    'content'   => $value->snippet
                ];
                $this->api->uploadSnippet($clone->number, $snippetData);
            }

            $condition = [
                'name'      => Config::FASTLY_MODLY_MODULE.'_pass',
                'statement' => 'req.http.x-pass',
                'type'      => 'REQUEST',
                'priority'  => 90
            ];

            $createCondition = $this->api->createCondition($clone->number, $condition);

            $request = [
                'action'            => 'pass',
                'max_stale_age'     => 3600,
                'name'              => Config::FASTLY_MODLY_MODULE.'_request',
                'request_condition' => $createCondition->name,
                'service_id'        => $service->id,
                'version'           => $currActiveVersion
            ];

            $this->api->createRequest($clone->number, $request);
            $this->api->validateServiceVersion($clone->number);

            $this->api->activateVersion($clone->number);

            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                $this->api->sendWebHook(
                    '*Upload VCL has been initiated and activated in version ' . $clone->number . '*'
                );
            }

            $comment = ['comment' => 'Magento Module uploaded the "'.$moduleId.'" Edge Module'];
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
