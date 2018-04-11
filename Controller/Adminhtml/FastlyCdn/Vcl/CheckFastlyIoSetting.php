<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Api;
use Magento\Framework\Controller\ResultInterface;

class CheckFastlyIoSetting extends Action
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
     * @var \Fastly\Cdn\Model\Api
     */
    private $api;

    /**
     * GetBackends constructor.
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Api $api
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Api $api
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->api = $api;
        parent::__construct($context);
    }

    /**
     * Get Fastly service image optimization status
     *
     * @return $this|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        try {
            $result = $this->resultJson->create();
            $req = $this->api->checkImageOptimizationStatus();

            if (!$req) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to check image optimization status.'
                ]);
            }

            return $result->setData([
                'status'    => true,
                'req_setting'   => $req
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
