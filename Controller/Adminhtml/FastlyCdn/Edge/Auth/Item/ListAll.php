<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Auth\Item;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Helper\Vcl;

class ListAll extends Action
{
    /**
     * Path to Authentication snippet
     */
    const VCL_AUTH_SNIPPET_PATH = '/vcl_snippets_basic_auth';

    /**
     * @var Http
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $resultJson;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Vcl
     */
    private $vcl;

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
            if (!$dictionary || (is_array($dictionary) && empty($dictionary))) {
                return $result->setData([
                    'status'    => 'empty',
                    'msg'       => 'Authentication dictionary does not exist.'
                ]);
            }

            $authItems = false;
            if (isset($dictionary->id)) {
                $authItems = $this->api->dictionaryItemsList($dictionary->id);
            }

            if (is_array($authItems) && empty($authItems)) {
                return $result->setData([
                    'status'    => 'empty',
                    'msg'       => 'There are no dictionary items.'
                ]);
            }

            if (!$authItems) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to fetch dictionary items.'
                ]);
            }

            foreach ($authItems as $key => $item) {
                $userData = explode(':', base64_decode($item->item_key)); // @codingStandardsIgnoreLine - used for authentication
                $username = $userData[0];
                $item->item_key_id = $item->item_key;
                $item->item_key = $username;
                $authItems[$key] = $item;
            }

            return $result->setData([
                'status'    => true,
                'auths'     => $authItems
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
