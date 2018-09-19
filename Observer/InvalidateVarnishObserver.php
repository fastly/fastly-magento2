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
namespace Fastly\Cdn\Observer;

use Fastly\Cdn\Helper\CacheTags;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\PurgeCache;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class InvalidateVarnishObserver
 *
 * @package Fastly\Cdn\Observer
 */
class InvalidateVarnishObserver implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var PurgeCache
     */
    private $purgeCache;
    /**
     * @var CacheTags
     */
    private $cacheTags;
    /**
     * @var array
     */
    private $alreadyPurged = [];

    /**
     * @param Config $config
     * @param PurgeCache $purgeCache
     * @param CacheTags $cacheTags
     */
    public function __construct(
        Config $config,
        PurgeCache $purgeCache,
        CacheTags $cacheTags
    ) {
        $this->config = $config;
        $this->purgeCache = $purgeCache;
        $this->cacheTags = $cacheTags;
    }

    /**
     * If Fastly CDN is enabled it sends one purge request per tag
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Zend_Uri_Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->config->getType() == Config::FASTLY && $this->config->isEnabled()) {
            $object = $observer->getEvent()->getObject();

            if ($object instanceof \Magento\Framework\DataObject\IdentityInterface && $this->canPurgeObject($object)) {
                $tags = [];
                foreach ($object->getIdentities() as $tag) {
                    $tag = $this->cacheTags->convertCacheTags($tag);
                    if (!in_array($tag, $this->alreadyPurged)) {
                        $tags[] = $tag;
                        $this->alreadyPurged[] = $tag;
                    }
                }

                if (!empty($tags)) {
                    $this->purgeCache->sendPurgeRequest(array_unique($tags));
                }
            }
        }
    }

    /**
     * Return false if purging is not allowed for object instance.
     *
     * @param \Magento\Framework\DataObject\IdentityInterface $object
     * @return bool
     */
    private function canPurgeObject(\Magento\Framework\DataObject\IdentityInterface $object)
    {
        if ($object instanceof \Magento\Catalog\Model\Category && !$this->config->canPurgeCatalogCategory()) {
            return false;
        }
        if ($object instanceof \Magento\Catalog\Model\Product && !$this->config->canPurgeCatalogProduct()) {
            return false;
        }
        if ($object instanceof \Magento\Cms\Model\Page && !$this->config->canPurgeCmsPage()) {
            return false;
        }
        return true;
    }
}
