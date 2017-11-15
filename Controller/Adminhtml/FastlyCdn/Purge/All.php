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

use Fastly\Cdn\Model;
use Magento\Framework\App\Cache;

class All extends \Magento\Backend\App\Action
{
    /**
     * @var \Fastly\Cdn\Model\Api
     */
    protected $api;
    protected $_cacheManager;


    /**
     * All constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param Model\Api $api
     * @param Cache\Manager $cacheManager
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Fastly\Cdn\Model\Api $api,
        \Magento\Framework\App\Cache\Manager $cacheManager
    ) {
        parent::__construct($context);
        $this->api = $api;
        $this->_cacheManager = $cacheManager;
    }

    /**
     * Purge by content type
     *
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Exception
     */
    public function execute()
    {
        $types = $this->_cacheManager->getAvailableTypes();
        // Clear Magento cache
        $this->_cacheManager->clean($types);
        // Clear Fastly cache
        $result = $this->api->cleanAll();
        if ($result) {
            $this->messageManager->addSuccessMessage(__('Full Magento & Fastly Cache has been cleaned.'));
        } else {
            $this->getMessageManager()->addErrorMessage(
                __('Full Magento & Fastly Cache was not cleaned successfully.')
            );
        }
        return $this->_redirect('*/cache/index');
    }
}