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
use Fastly\Cdn\Helper\Vcl;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class ServiceInfo
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Configuration
 */
class ServiceInfo extends Action
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
     * @var Vcl
     */
    private $vcl;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * ServiceInfo constructor.
     *
     * @param Context $context
     * @param Config $config
     * @param Api $api
     * @param JsonFactory $resultJsonFactory
     * @param Vcl $vcl
     */
    public function __construct(
        Context $context,
        Config $config,
        Api $api,
        JsonFactory $resultJsonFactory,
        Vcl $vcl
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->vcl = $vcl;

        parent::__construct($context);
    }

    /**
     * Checking service details
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        try {
            $service = $this->api->checkServiceDetails();
            return $result->setData([
                'status'            => true,
                'service'           => $service,
                'active_version'    => $this->vcl->getCurrentVersion($service->versions),
                'next_version'      => $this->vcl->getNextVersion($service->versions),
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
