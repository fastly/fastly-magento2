<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Acl;

use \Magento\Framework\App\Request\Http;
use \Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Api;

class ListAll extends \Magento\Backend\App\Action
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
     * ListAll constructor
     *
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
     * Get all ACLs for active version
     *
     * @return $resultJsonFactory
     */
    public function execute()
    {
        $result = $this->resultJson->create();

        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $acls = $this->api->getAcls($activeVersion);

            if(is_array($acls) && empty($acls))
            {
                return $result->setData(array('status' => true, 'acls' => []));
            }

            if(!$acls) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to fetch ACLs.'));
            }

            return $result->setData(array('status' => true, 'acls' => $acls));
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }
}