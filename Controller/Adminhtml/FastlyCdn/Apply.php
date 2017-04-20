<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn;

use \Magento\Framework\App\Request\Http;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Fastly\Cdn\Model\Api;

class Apply extends \Magento\Backend\App\Action
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
     * @var Api
     */
    protected $api;

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

    public function execute()
    {
        $result = $this->resultJson->create();
        $from = $this->getRequest()->getParam('from');
        $to = $this->getRequest()->getParam('to');
        $sampleRate = $this->getRequest()->getParam('sample_rate');
        $region = $this->getRequest()->getParam('region');

        if (!$from || !$to) {
            return $result->setData(array('status' => false, 'msg' => 'Please enter dates.'));
        }

        /* Convert datetime to timestamp */
        $fromTimestamp = strtotime($from);
        $toTimestamp = strtotime($to);

        /* Parameters array */
        $parameters = [];
        $parameters['from'] = $fromTimestamp;
        $parameters['to'] = $toTimestamp;
        $parameters['sample_rate'] = $sampleRate;
        $parameters['region'] = $region;

        $queryResult = $this->api->queryHistoricStats($parameters);

        return $result->setData(array('status' => true, 'stats' => $queryResult));
    }
}