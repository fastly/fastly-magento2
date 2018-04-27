<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\ImageOptimization;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Api;

class ListAll extends Action
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
     * ListAll constructor
     *
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
     * List all image optimization default config options
     *
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();

        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $ioOptions = $this->api->getImageOptimizationDefaultConfigOptions($activeVersion)->data->attributes;

            if (!$ioOptions) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to fetch image optimization default config options.'
                ]);
            }

            return $result->setData([
                'status'        => true,
                'io_options'    => $ioOptions
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
