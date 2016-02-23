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
 * @package     Fastly_CDN
 * @copyright   Copyright (c) 2016 Fastly, Inc. (http://www.fastly.com)
 * @license     BSD, see LICENSE_FASTLY_CDN.txt
 */
namespace Fastly\CDN\Observer;

use Fastly\CDN\Model\Config;
use Fastly\CDN\Model\PurgeCache;
use Magento\Framework\Event\ObserverInterface;

class InvalidateVarnishObserver implements ObserverInterface
{
    /**
     * Application config object
     *
     * @var Config
     */
    protected $config;

    /**
     * @var PurgeCache
     */
    protected $purgeCache;

    /**
     * @param Config $config
     * @param PurgeCache $purgeCache
     */
    public function __construct(Config $config, PurgeCache $purgeCache)
    {
        $this->config = $config;
        $this->purgeCache = $purgeCache;
    }

    /**
     * If Fastly CDN is enabled it sends one purge request per tag.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->config->getType() == Config::FASTLY && $this->config->isEnabled()) {
            $object = $observer->getEvent()->getObject();
            if ($object instanceof \Magento\Framework\DataObject\IdentityInterface && $this->canPurgeObject($object)) {
                foreach ($object->getIdentities() as $tag) {
                    $result = $this->purgeCache->sendPurgeRequest($tag);
                }
            }
        }

        // @TODO implement message for admin
    }

    /**
     * Return false if purging is not allowed for object instance.
     *
     * @param \Magento\Framework\DataObject\IdentityInterface $object
     * @return bool
     */
    protected function canPurgeObject(\Magento\Framework\DataObject\IdentityInterface $object)
    {
        if ($object instanceof \Magento\Catalog\Model\Category && !$this->config->getPurgeCatalogCategory()) {
            return false;
        }
        if ($object instanceof \Magento\Catalog\Model\Product && !$this->config->getPurgeCatalogProduct()) {
            return false;
        }
        if ($object instanceof \Magento\Cms\Model\Page && !$this->config->getPurgeCmsPage()) {
            return false;
        }
        return true;
    }
}
