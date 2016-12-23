<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use \Magento\Framework\App\Request\Http;
use \Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Api;

class GetBackends extends \Magento\Backend\App\Action
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
     * GetBackends constructor.
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
     * Get all backends for active version
     *
     * @return $resultJsonFactory
     */
    public function execute()
    {
        try {
            $result = $this->resultJson->create();
            $activeVersion = $this->getRequest()->getParam('active_version');
            $backends = $this->api->getBackends($activeVersion);

            if(!$backends) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to check Backend details.'));
            }

            return $result->setData(array('status' => true, 'backends' => $backends));
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }
}