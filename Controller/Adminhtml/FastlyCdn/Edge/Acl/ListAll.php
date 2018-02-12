<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Acl;

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

            if (is_array($acls) && empty($acls)) {
                return $result->setData([
                    'status'    => true,
                    'acls' => []
                ]);
            }

            if (!$acls) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to fetch ACLs.'
                ]);
            }

            return $result->setData([
                'status'    => true,
                'acls'      => $acls
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
