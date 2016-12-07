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

class CheckTlsSetting extends \Magento\Backend\App\Action
{
    const FORCE_TLS_SETTING_NAME = 'magentomodule_force_tls';

    /**
     * @var \Fastly\Cdn\Model\Api
     */
    protected $api;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * CheckTlsSetting constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param Config $config
     * @param Api $api
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Config $config,
        Api $api,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Checking request setting
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $result = $this->resultJsonFactory->create();
            $req = $this->api->getRequest($activeVersion, self::FORCE_TLS_SETTING_NAME);

            if(!$req) {
                return $result->setData(array('status' => false));
            }

            return $result->setData(array('status' => true, 'req_setting' => $req));
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }
}
