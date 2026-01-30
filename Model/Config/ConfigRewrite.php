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
namespace Fastly\Cdn\Model\Config;

use Fastly\Cdn\Model\Api;
use Magento\AsyncConfig\Setup\ConfigOptionsList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\StoreGraphQl\Model\Resolver\Store\ConfigIdentity;
use Magento\StoreGraphQl\Model\Resolver\Store\StoreConfigDataProvider;
use Magento\Store\Model\StoreManagerInterface;
use Fastly\Cdn\Model\PurgeCache;

/**
 * Used for sending purge after disabling Fastly as caching service
 */
class ConfigRewrite
{
    /**
     * @var bool
     */
    private $purge = false;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig = null;
    /**
     * @var Api
     */
    private $api;

    /**
     * @var ConfigIdentity
     */
    private $configIdentity;

    /**
     * @var StoreConfigDataProvider
     */
    private $storeConfigDataProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PurgeCache
     */
    private $purgeCache;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Api $api
     * @param ConfigIdentity $configIdentity
     * @param StoreConfigDataProvider $storeConfigDataProvider
     * @param StoreManagerInterface $storeManager
     * @param PurgeCache $purgeCache
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Api $api,
        ConfigIdentity $configIdentity,
        StoreConfigDataProvider $storeConfigDataProvider,
        StoreManagerInterface $storeManager,
        PurgeCache $purgeCache,
        DeploymentConfig $deploymentConfig,
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->api = $api;
        $this->configIdentity = $configIdentity;
        $this->storeConfigDataProvider = $storeConfigDataProvider;
        $this->storeManager = $storeManager;
        $this->purgeCache = $purgeCache;
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * Trigger purge if set
     *
     * @param \Magento\Config\Model\Config $subject
     * @return void
     */
    public function afterSave(\Magento\Config\Model\Config $subject): void // @codingStandardsIgnoreLine - unused parameter
    {
        if ($this->purge) {
            $this->api->cleanBySurrogateKey(['text']);
        }

        if (!$this->deploymentConfig->get(ConfigOptionsList::CONFIG_PATH_ASYNC_CONFIG_SAVE)) {
            return;
        }

        $store = $subject->getStore();

        $resolvedData = $this->storeConfigDataProvider->getStoreConfigData($this->storeManager->getStore($store));
        $tags = $this->configIdentity->getIdentities($resolvedData);
        if (!empty($tags)) {
            $this->purgeCache->sendPurgeRequest(array_unique($tags));
        }
    }

    /**
     * Set flag for purging if Fastly is switched off
     *
     * @param \Magento\Config\Model\Config $subject
     * @return void
     */
    public function beforeSave(\Magento\Config\Model\Config $subject): void
    {
        $data = $subject->getData();
        if (!empty($data['groups']['full_page_cache']['fields']['caching_application']['value'])) {
            $currentCacheConfig = $data['groups']['full_page_cache']['fields']['caching_application']['value'];
            $oldCacheConfig = $this->scopeConfig->getValue(\Magento\PageCache\Model\Config::XML_PAGECACHE_TYPE);

            if ($oldCacheConfig == \Fastly\Cdn\Model\Config::FASTLY && $currentCacheConfig != $oldCacheConfig) {
                $this->purge = true;
            }
        }
    }
}
