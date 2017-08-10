<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Auth\Item;

use \Magento\Framework\App\Request\Http;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;

class ListAll extends \Magento\Backend\App\Action
{

    /**
     * Path to Authentication snippet
     */
    const VCL_AUTH_SNIPPET_PATH = '/vcl_snippets_basic_auth';

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
     * @var Config
     */
    protected $config;

    /**
     * @var Vcl
     */
    protected $vcl;

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
     * Get all Auth items for active version
     *
     * @return $resultJsonFactory
     */
    public function execute()
    {
        $result = $this->resultJson->create();

        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $dictionary = $this->api->getSingleDictionary($activeVersion, 'magentomodule_basic_auth');

            // Fetch Authentication items
            if(is_array($dictionary) && empty($dictionary))
            {
                return $result->setData(array('status' => 'empty', 'msg' => 'Authentication dictionary does not exist.'));
            }

            $authItems = false;

            if(isset($dictionary->id))
            {
                $authItems = $this->api->dictionaryItemsList($dictionary->id);
            }

            if(is_array($authItems) && empty($authItems))
            {
                return $result->setData(array('status' => 'empty', 'msg' => 'There are no dictionary items.'));
            }

            if(!$authItems) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to fetch dictionary items.'));
            }

            foreach($authItems as $key => $item) {
                $userData = explode(':', base64_decode($item->item_key));
                $username = $userData[0];
                $item->item_key_id = $item->item_key;
                $item->item_key = $username;
                $authItems[$key] = $item;
            }

            return $result->setData(array('status' => true, 'auths' => $authItems));
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }
}