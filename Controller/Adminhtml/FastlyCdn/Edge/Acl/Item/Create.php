<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Acl\Item;

use \Magento\Framework\App\Request\Http;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;

class Create extends \Magento\Backend\App\Action
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
     * @var Config
     */
    protected $config;

    /**
     * @var \Fastly\Cdn\Model\Api
     */
    protected $api;

    /**
     * @var Vcl
     */
    protected $vcl;

    /**
     * ForceTls constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl
    )
    {
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
        try {
            $result = $this->resultJson->create();
            $aclId = $this->getRequest()->getParam('acl_id');
            $value = $this->getRequest()->getParam('item_value');

            // Handle subnet
            $ipParts = explode('/', $value);
            $subnet = false;
            if(!empty($ipParts[1])) {
                if(is_numeric($ipParts[1]) && (int)$ipParts[1] < 129) {
                    $subnet = $ipParts[1];
                } else {
                    return $result->setData(array('status' => false, 'msg' => 'Invalid IP subnet format.'));
                }
            }

            if (!filter_var($ipParts[0], FILTER_VALIDATE_IP)) {
                return $result->setData(array('status' => false, 'msg' => 'Invalid IP address format.'));
            }

            $createAclItem = $this->api->upsertAclItem($aclId, $ipParts[0], $subnet);

            if(!$createAclItem) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to create Acl entry.'));
            }

            return $result->setData(array('status' => true, 'id' => $createAclItem->id));
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }
}
