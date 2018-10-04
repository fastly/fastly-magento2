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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Configuration;

use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Fastly\Cdn\Model\Statistic;
use Fastly\Cdn\Model\StatisticFactory;
use Fastly\Cdn\Model\StatisticRepository;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class TestConnection
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Configuration
 */
class TestConnection extends Action
{
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var Statistic
     */
    private $statistic;
    /**
     * @var StatisticFactory
     */
    private $statisticFactory;
    /**
     * @var StatisticRepository
     */
    private $statisticRepository;

    /**
     * TestConnection constructor.
     *
     * @param Context $context
     * @param Config $config
     * @param Api $api
     * @param JsonFactory $resultJsonFactory
     * @param Statistic $statistic
     * @param StatisticFactory $statisticFactory
     * @param StatisticRepository $statisticRepository
     */
    public function __construct(
        Context $context,
        Config $config,
        Api $api,
        JsonFactory $resultJsonFactory,
        Statistic $statistic,
        StatisticFactory $statisticFactory,
        StatisticRepository $statisticRepository
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->statistic = $statistic;
        $this->statisticFactory = $statisticFactory;
        $this->statisticRepository = $statisticRepository;

        parent::__construct($context);
    }

    /**
     * Check service details
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $serviceId = $this->getRequest()->getParam('service_id');
        $apiKey = $this->getRequest()->getParam('api_key');

        try {
            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                $this->api->sendWebHook('*initiated test connection action*');
            }

            $service = $this->api->checkServiceDetails(true, $serviceId, $apiKey);
            $sendValidationReq = $this->statistic->sendValidationRequest(true, $serviceId);
            $this->saveValidationState(true, $sendValidationReq);
        } catch (\Exception $e) {
            $sendValidationReq = $this->statistic->sendValidationRequest(false, $serviceId);
            $this->saveValidationState(false, $sendValidationReq);
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }

        return $result->setData([
            'status'        => true,
            'service_name'  => $service->name
        ]);
    }

    private function saveValidationState($serviceStatus, $gaRequestStatus)
    {
        $validationStat = $this->statisticFactory->create();
        $validationStat->setAction(Statistic::FASTLY_VALIDATION_FLAG);
        $validationStat->setSent($gaRequestStatus);
        $validationStat->setState($serviceStatus);
        $this->statisticRepository->save($validationStat);
    }
}
