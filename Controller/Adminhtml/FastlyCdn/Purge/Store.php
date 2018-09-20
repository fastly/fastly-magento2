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

use Fastly\Cdn\Model\PurgeCache;
use Fastly\Cdn\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Store
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Purge
 */
class Store extends Action
{
    /**
     * @var PurgeCache
     */
    private $purgeCache;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Config
     */
    private $config;

    /**
     * Store constructor.
     *
     * @param Context $context
     * @param PurgeCache $purgeCache
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     */
    public function __construct(
        Context $context,
        PurgeCache $purgeCache,
        StoreManagerInterface $storeManager,
        Config $config
    ) {
        $this->purgeCache = $purgeCache;
        $this->storeManager = $storeManager;
        $this->config = $config;

        parent::__construct($context);
    }

    /**
     * Purge by content type
     *
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Exception
     */
    public function execute()
    {
        try {
            if ($this->config->getType() == Config::FASTLY && $this->config->isEnabled()) {
                // check if store exists
                $storeId = $this->getRequest()->getParam('stores', false);
                /** @var \Magento\Store\Model\Store $store */
                $store = $this->storeManager->getStore($storeId);
                if (!$store->getId()) {
                    throw new LocalizedException(__('Invalid store "'.$storeId.'" given.'));
                }

                $result = $this->purgeCache->sendPurgeRequest($store->getIdentities());

                if ($result) {
                    $this->getMessageManager()->addSuccessMessage(__('The Fastly CDN has been cleaned.'));
                } else {
                    $this->getMessageManager()->addErrorMessage(
                        __('The purge request was not processed successfully.')
                    );
                }
            }
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage(
                __('An error occurred while clearing the Fastly CDN: ') . $e->getMessage()
            );
        }
        return $this->_redirect('*/cache/index');
    }
}
