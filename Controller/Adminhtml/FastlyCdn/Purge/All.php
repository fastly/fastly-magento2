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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Purge;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Cache\Manager;

/**
 * Class All
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Purge
 */
class All extends Action
{
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Manager
     */
    private $cacheManager;

    /**
     * All constructor.
     *
     * @param Context $context
     * @param Api $api
     * @param Manager $cacheManager
     */
    public function __construct(
        Context $context,
        Api $api,
        Manager $cacheManager
    ) {
        $this->api = $api;
        $this->cacheManager = $cacheManager;

        parent::__construct($context);
    }

    /**
     * Performs cache cleanup and purge all on Fastly service.
     * Should be used when "Preserve static assets on purge" is enabled.
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Zend_Uri_Exception
     */
    public function execute()
    {
        // Flush all Magento caches
        $types = $this->cacheManager->getAvailableTypes();
        $types = array_diff($types, ['full_page']); // FPC is Handled separately

        $this->cacheManager->clean($types);

        // Purge everything from Fastly
        $result = $this->api->cleanAll();

        if ($result === true) {
            $this->messageManager->addSuccessMessage(
                __('Full Magento & Fastly Cache has been cleaned.')
            );
        } else {
            $this->getMessageManager()->addErrorMessage(
                __('Full Magento & Fastly Cache was not cleaned successfully.')
            );
        }

        return $this->_redirect('*/cache/index');
    }
}
