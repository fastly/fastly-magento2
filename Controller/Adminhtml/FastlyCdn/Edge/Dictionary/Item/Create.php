<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Dictionary\Item;

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

    public function execute()
    {
        $result = $this->resultJson->create();

        try {
            $dictionaryId = $this->getRequest()->getParam('dictionary_id');
            $value = $this->getRequest()->getParam('item_value');
            $key = $this->getRequest()->getParam('item_key');

            $createDictionaryItem = $this->api->upsertDictionaryItem($dictionaryId, $key, $value);

            if(!$createDictionaryItem) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to create Dictionary item.'));
            }

            return $result->setData(array('status' => true));
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }
}
