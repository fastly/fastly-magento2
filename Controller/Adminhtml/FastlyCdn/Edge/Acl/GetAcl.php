<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Acl;

use \Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Acl;

class GetAcl extends \Magento\Backend\App\Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJson;

    /**
     * @var \Fastly\Cdn\Model\Api
     */
    protected $api;

    /**
     * @var Acl
     */
    protected $acl;

    /**
     * GetAcl constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Api $api
     * @param Acl $acl
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        Api $api,
        Acl $acl
    )
    {
        $this->resultJson = $resultJsonFactory;
        $this->api = $api;
        $this->acl = $acl;
        parent::__construct($context);
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();

        try {
            $service = $this->api->checkServiceDetails();
            if(!$service) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to check Service details.'));
            }

            return $result->$this->acl->determineVersions($service->versions);

        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }
}