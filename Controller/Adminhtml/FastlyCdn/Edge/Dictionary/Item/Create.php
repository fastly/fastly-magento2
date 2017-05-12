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
        try {
            $result = $this->resultJson->create();
            $dictionaryId = $this->getRequest()->getParam('dictionary_id');
            $values = $this->getRequest()->getParam('values');
            $keys = $this->getRequest()->getParam('keys');
            $oldItems = $this->getRequest()->getParam('old_items');

            $newItems = [];
            for ($i=0; $i < count($values); $i++) {
                $newItems['items'][] = ['op' => 'create', 'item_key' => $keys[$i], 'item_value' => $values[$i]];
            }
            $newItems = json_encode($newItems);

            $deleteItems = [];
            if (is_array($oldItems)) {
                foreach ($oldItems as $oldItem) {
                    $deleteItems['items'][] = ['op' => 'delete', 'item_key' => $oldItem['item_key']];
                }
            }

            $service = $this->api->checkServiceDetails();

            if(!$service) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to check Service details.'));
            }

            if (!empty($deleteItems))
            {
                $deleteItems = json_encode($deleteItems);
                $deleteDictionaryItems = $this->api->createDictionaryItems($dictionaryId, $deleteItems);
                if (!$deleteDictionaryItems) {
                    return $result->setData(array('status' => false, 'msg' => 'Failed to create Dictionary container.'));
                }
            }

            $createDictionaryItems = $this->api->createDictionaryItems($dictionaryId, $newItems);

            if(!$createDictionaryItems) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to create Dictionary container.'));
            }

            return $result->setData(array('status' => true));
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }
}
