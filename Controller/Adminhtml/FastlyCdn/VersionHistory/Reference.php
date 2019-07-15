<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\VersionHistory;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;

class Reference extends Action
{
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Http
     */
    private $request;
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    public function __construct
    (
        Action\Context $context,
        Api $api,
        Http $request,
        JsonFactory $jsonFactory
    )
    {
        parent::__construct($context);
        $this->api = $api;
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        //todo: try catches (vidi kako se radilo u ostalim fileovima)
        $result = $this->jsonFactory->create();
        $version = $this->request->getParam('version');
        $response = $this->api->getGeneratedVcl($version);
        return $result->setData([
           'version' => $response->version,
           'content' => $response->content
        ]);
    }
}