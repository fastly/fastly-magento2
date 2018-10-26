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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\BasicAuthentication;

use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class CheckAuthUsersAvailable
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\BasicAuthentication
 */
class CheckAuthUsersAvailable extends Action
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
     * CheckTlsSetting constructor.
     *
     * @param Context $context
     * @param Config $config
     * @param Api $api
     * @param JsonFactory $resultJsonFactory
     */
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

    /**
     * Check if authentication users are available
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $dictionaryName = Config::AUTH_DICTIONARY_NAME;
            $dictionary = $this->api->getSingleDictionary($activeVersion, $dictionaryName);

            if ((is_array($dictionary) && empty($dictionary)) || $dictionary == false || !isset($dictionary->id)) {
                return $result->setData([
                    'status'    => 'empty',
                    'msg'       => 'Basic Authentication cannot be enabled because there are no users assigned to it.'
                ]);
            } else {
                $authItems = $this->api->dictionaryItemsList($dictionary->id);

                if (is_array($authItems) && empty($authItems)) {
                    return $result->setData([
                        'status' => 'empty',
                        'msg'    => 'Basic Authentication cannot be enabled because there are no users assigned to it.'
                    ]);
                }

                return $result->setData(['status' => true]);
            }
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
