<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Fastly\Cdn\Model\Config;
use \Magento\Framework\App\Request\Http;
use \Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Api;

class GetErrorPageRespObj extends \Magento\Backend\App\Action
{
    /**
     * @var Http
     */
    protected $request;

    /**
     * @var JsonFactory
     */
    protected $resultJson;

    /**
     * @var \Fastly\Cdn\Model\Api
     */
    protected $api;

    /**
     * GetErrorPageRespObj constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Api $api
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Api $api
    )
    {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->api = $api;
        parent::__construct($context);
    }

    /**
     * Get Error Page Response Object
     *
     * @return $resultJsonFactory
     */
    public function execute()
    {
        try {
            $result = $this->resultJson->create();
            $activeVersion = $this->getRequest()->getParam('active_version');
            $response = $this->api->getResponse($activeVersion, Config::ERROR_PAGE_RESPONSE_OBJECT);

            if(!$response) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to fetch Error page Response object.'));
            }

            return $result->setData(array('status' => true, 'errorPageResp' => $response));
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }
}