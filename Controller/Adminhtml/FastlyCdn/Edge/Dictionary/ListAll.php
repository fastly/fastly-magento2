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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Dictionary;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class ListAll
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Dictionary
 */
class ListAll extends Action
{
    const ADMIN_RESOURCE = 'Magento_Config::config';

    const NGWAF_DICTIONARY_NAME = "Edge_Security";

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
     * ListAll constructor
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
     * Get all dictionaries for active version
     *
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();

        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $dictionaries = $this->api->getDictionaries($activeVersion);

            if (is_array($dictionaries) && empty($dictionaries)) {
                return $result->setData([
                    'status'        => true,
                    'dictionaries'  => []
                ]);
            }

            if (!$dictionaries) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to fetch dictionaries.'
                ]);
            }

            // This dictionary represents NGWAF, used while migrating customers from WAF. Adobe is requesting that
            // their customers shouldn't be able to disable it, so we remove it from Admin listing.
            foreach ($dictionaries as $key => $dictionary) {
                if (isset($dictionary->name) && $dictionary->name === self::NGWAF_DICTIONARY_NAME) {
                    array_splice($dictionaries, $key, 1);
                }
            }

            return $result->setData([
                'status'        => true,
                'dictionaries'  => $dictionaries
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
