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
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Used for sending purge after disabling Fastly as caching service
 *
 * @author Inchoo
 */
class ConfigRewrite
{
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
     * ConfigRewrite constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Api $api
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Api $api
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->api = $api;
    }

    /**
     * Trigger purge if set
     *
     * @param \Magento\Config\Model\Config $subject
     * @throws \Zend_Uri_Exception
     */
    public function afterSave(\Magento\Config\Model\Config $subject) // @codingStandardsIgnoreLine - unused parameter
    {
        if ($this->purge) {
            $this->api->cleanBySurrogateKey(['text']);
        }
    }

    /**
     * Set flag for purging if Fastly is switched off
     * @param \Magento\Config\Model\Config $subject
     */
    public function beforeSave(\Magento\Config\Model\Config $subject)
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
