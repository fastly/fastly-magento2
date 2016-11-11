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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use \Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Helper\Vcl;

class CustomerInfo extends \Magento\Backend\App\Action
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
     * ServiceInfo constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param Config $config
     * @param Api $api
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Config $config,
        Api $api,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
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
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        try {
            $result = $this->resultJsonFactory->create();
            $customer = $this->api->getCustomerInfo();

            if(!$customer) {
                return $result->setData(array('status' => false));
            }

            return $result->setData(array('status' => true, 'customer' => $customer));
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }
}
