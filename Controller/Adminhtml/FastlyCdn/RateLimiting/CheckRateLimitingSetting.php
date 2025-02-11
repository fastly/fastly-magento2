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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\RateLimiting;

use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class CheckRateLimitingSetting extends Action
{
    const ADMIN_RESOURCE = 'Magento_Config::config';

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

    public function __construct(
        Context $context,
        Config $config,
        Api $api,
        JsonFactory $resultJsonFactory
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->resultJsonFactory = $resultJsonFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $req = $this->api->getRequest($activeVersion, Config::RATE_LIMITING_SETTING_NAME);

            if ($req == false) {
                return $result->setData([
                    'status' => false
                ]);
            }

            return $result->setData([
                'status'        => true,
                'req_setting'   => $req
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
