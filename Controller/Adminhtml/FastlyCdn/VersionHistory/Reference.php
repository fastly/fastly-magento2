<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\VersionHistory;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class Reference
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\VersionHistory
 */
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

    /**
     * Reference constructor.
     * @param Action\Context $context
     * @param Api $api
     * @param Http $request
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Action\Context $context,
        Api $api,
        Http $request,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->api = $api;
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Get version id, and calls API that returns generated VCL for the specific version id
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        $version = (int)$this->request->getParam('version');
        $response = $this->api->getGeneratedVcl($version);
        if (!$response) {
            return $result->setData([
                    'status' => false,
                    'msg' => 'There is no version #' . $version
                ]);
        }

        return $result->setData([
                'status' => true,
                'version' => $response->version,
                'content' => $response->content
            ]);
    }
}
