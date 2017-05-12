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

use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use \Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Helper\Vcl;
use Fastly\Cdn\Model\Statistic;
use Fastly\Cdn\Model\StatisticFactory;
use Fastly\Cdn\Model\StatisticRepository;

class TestConnection extends \Magento\Backend\App\Action
{
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
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Statistic
     */
    protected $_statistic;

    /**
     * @var StatisticFactory
     */
    protected $_statisticFactory;

    /**
     * @var StatisticRepository
     */
    protected $_statisticRepository;

    /**
     * TestConnection constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param Config $config
     * @param Api $api
     * @param JsonFactory $resultJsonFactory
     * @param Statistic $statistic
     * @param StatisticFactory $statisticFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Config $config,
        Api $api,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        Statistic $statistic,
        StatisticFactory $statisticFactory,
        StatisticRepository $statisticRepository
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_statistic = $statistic;
        $this->_statisticFactory = $statisticFactory;
        $this->_statisticRepository = $statisticRepository;

        parent::__construct($context);
    }

    /**
     * Checking service details
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        try {
            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                $this->api->sendWebHook('*initiated test connection action*');
            }

            $result = $this->resultJsonFactory->create();
            $serviceId = $this->getRequest()->getParam('service_id');
            $apiKey = $this->getRequest()->getParam('api_key');

            $service = $this->api->checkServiceDetails(true, $serviceId, $apiKey);

            if(!$service) {
                $sendValidationReq = $this->_statistic->sendValidationRequest(false, $serviceId);
                $this->_saveValidationState(false, $sendValidationReq);
                return $result->setData(array('status' => false));
            }
            
            $sendValidationReq = $this->_statistic->sendValidationRequest(true, $serviceId);
            $this->_saveValidationState(true, $sendValidationReq);

            return $result->setData(array('status' => true, 'service_name' => $service->name));
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }

    protected function _saveValidationState($serviceStatus, $gaRequestStatus)
    {
        $validationStat = $this->_statisticFactory->create();
        $validationStat->setAction(Statistic::FASTLY_VALIDATION_FLAG);
        $validationStat->setSent($gaRequestStatus);
        $validationStat->setState($serviceStatus);
        $this->_statisticRepository->save($validationStat);
    }
}
