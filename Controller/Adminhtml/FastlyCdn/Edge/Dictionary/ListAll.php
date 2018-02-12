<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Dictionary;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;

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
     * @var Api
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
     * Get all dictionaries for active version
     *
     * @return $resultJsonFactory
     */
    public function execute()
    {
        $result = $this->resultJson->create();

        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $dictionaries = $this->api->getDictionaries($activeVersion);

            if (is_array($dictionaries) && empty($dictionaries)) {
                return $result->setData([
                    'status'        => true,
                    'dictionaries'  => []
                ]);
            }

            if (!$dictionaries) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to fetch dictionaries.'
                ]);
            }

            return $result->setData([
                'status'        => true,
                'dictionaries'  => $dictionaries
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
