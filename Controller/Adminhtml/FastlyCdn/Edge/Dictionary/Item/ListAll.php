<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Dictionary\Item;

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
     * Get all dictionaries for active version
     *
     * @return $resultJsonFactory
     */
    public function execute()
    {
        $result = $this->resultJson->create();

        try {
            $dictionaryId = $this->getRequest()->getParam('dictionary_id');
            $dictionaryItems = $this->api->dictionaryItemsList($dictionaryId);

            if(is_array($dictionaryItems) && empty($dictionaryItems))
            {
                return $result->setData(array('status' => 'empty', 'msg' => 'There are no dictionary items.'));
            }

            if(!$dictionaryItems) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to fetch dictionary items.'));
            }

            return $result->setData(array('status' => true, 'dictionaryItems' => $dictionaryItems));
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }
}