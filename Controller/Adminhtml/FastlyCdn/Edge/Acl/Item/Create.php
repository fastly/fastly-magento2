<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Acl\Item;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;

class Create extends Action
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
     * @var Config
     */
    private $config;

    /**
     * @var \Fastly\Cdn\Model\Api
     */
    private $api;

    /**
     * @var Vcl
     */
    private $vcl;

    /**
     * ForceTls constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;

        parent::__construct($context);
    }

    /**
     * Create ACL entry for specific ACL
     *
     * @return $resultJsonFactory
     */
    public function execute()
    {
        $result = $this->resultJson->create();

        try {
            $aclId = $this->getRequest()->getParam('acl_id');
            $value = $this->getRequest()->getParam('item_value');
            $negated = $this->getRequest()->getParam('negated_field');

            // Handle subnet
            $ipParts = explode('/', $value);
            $subnet = false;
            if (!empty($ipParts[1])) {
                if (is_numeric($ipParts[1]) && (int)$ipParts[1] < 129) {
                    $subnet = $ipParts[1];
                } else {
                    return $result->setData([
                        'status'    => false,
                        'msg'       => 'Invalid IP subnet format.'
                    ]);
                }
            }

            if (!filter_var($ipParts[0], FILTER_VALIDATE_IP)) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Invalid IP address format.'
                ]);
            }

            $createAclItem = $this->api->upsertAclItem($aclId, $ipParts[0], $negated, $subnet);

            if (!$createAclItem) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to create Acl entry.'
                ]);
            }

            return $result->setData([
                'status'    => true,
                'id'        => $createAclItem->id
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
