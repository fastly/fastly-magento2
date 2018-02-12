<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Acl\Item;

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
     * Get all ACL entries for active version and current ACL
     *
     * @return $resultJsonFactory
     */
    public function execute()
    {
        $result = $this->resultJson->create();

        try {
            $aclId = $this->getRequest()->getParam('acl_id');
            $aclItems = $this->api->aclItemsList($aclId);

            if (is_array($aclItems) && empty($aclItems)) {
                return $result->setData([
                    'status'    => 'empty',
                    'msg'       => 'There are no acl items.'
                ]);
            }

            if (!$aclItems) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to fetch acl items.'
                ]);
            }

            return $result->setData([
                'status'    => true,
                'aclItems'  => $aclItems
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
