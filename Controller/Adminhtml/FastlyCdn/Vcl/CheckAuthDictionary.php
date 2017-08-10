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

use Fastly\Cdn\Model\Api;
use \Magento\Framework\Controller\Result\JsonFactory;

class CheckAuthDictionary extends \Magento\Backend\App\Action
{

    /**
     * @var \Fastly\Cdn\Model\Api
     */
    protected $api;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * CheckTlsSetting constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param Api $api
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Api $api,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->api = $api;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }


    /**
     * Check if AUTH dictionary exists
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $activeVersion = $this->getRequest()->getParam('active_version');

            $dictonaryName = \Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl\CheckAuthSetting::AUTH_DICTIONARY_NAME;
            $dictionary = $this->api->getSingleDictionary($activeVersion, $dictonaryName);

            if((is_array($dictionary) && empty($dictionary)) || $dictionary == false)
            {
                return $result->setData(array('status' => false));
            } else {
                return $result->setData(array('status' => true));
            }
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }
}
