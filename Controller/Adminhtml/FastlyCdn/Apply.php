<?php
/**
 * Fastly CDN for Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Fastly CDN for Magento End User License Agreement
 * that is bundled with this package in the file LICENSE_FASTLY_CDN.txt.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Fastly CDN to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Fastly
 * @package     Fastly_Cdn
 * @copyright   Copyright (c) 2016 Fastly, Inc. (http://www.fastly.com)
 * @license     BSD, see LICENSE_FASTLY_CDN.txt
 */
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Api;

/**
 * Class Apply
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn
 */
class Apply extends Action
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
     * Apply constructor.
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
     * Apply range
     *
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        $from = $this->getRequest()->getParam('from');
        $to = $this->getRequest()->getParam('to');
        $sampleRate = $this->getRequest()->getParam('sample_rate');
        $region = $this->getRequest()->getParam('region');

        if (!$from || !$to) {
            return $result->setData([
                'status'    => false,
                'msg'       => 'Please enter dates.'
            ]);
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

        return $result->setData([
            'status'    => true,
            'stats'     => $queryResult
        ]);
    }
}
