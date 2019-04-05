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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Maintenance;

use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class CheckSuSetting
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Maintenance
 */
class CheckSuSetting extends Action
{
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Vcl
     */
    private $vcl;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * CheckSuSetting constructor.
     * @param Context $context
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        Config $config,
        Api $api,
        Vcl $vcl,
        JsonFactory $resultJsonFactory
    ) {
        $this->api = $api;
        $this->vcl = $vcl;
        $this->config = $config;
        $this->resultJsonFactory = $resultJsonFactory;

        parent::__construct($context);
    }

    /**
     * Verifies whether super users are enabled in the magentomodule_config dictionary
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        try {
            $service = $this->api->checkServiceDetails();
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);

            $dictionaryName = Config::CONFIG_DICTIONARY_NAME;
            $dictionary = $this->api->getSingleDictionary($currActiveVersion, $dictionaryName);

            if (!$dictionary) {
                return $result->setData([
                    'status' => false
                ]);
            }

            $dictionaryItems = $this->api->dictionaryItemsList($dictionary->id);

            foreach ($dictionaryItems as $item) {
                if ($item->item_key == Config::CONFIG_DICTIONARY_KEY && $item->item_value == 1) {
                    return $result->setData([
                        'status' => true
                    ]);
                }
            }

            return $result->setData([
                'status' => false
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
