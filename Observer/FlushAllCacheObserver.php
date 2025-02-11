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

use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\PurgeCache;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 * Class FlushAllCacheObserver - send purge request
 *
 */
class FlushAllCacheObserver implements ObserverInterface
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
     * @param Config $config
     * @param PurgeCache $purgeCache
     */
    public function __construct(Config $config, PurgeCache $purgeCache)
    {
        $this->config = $config;
        $this->purgeCache = $purgeCache;
    }

    /**
     * Flush Fastly CDN cache
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void // @codingStandardsIgnoreLine - unused parameter
    {
        if ($this->config->getType() === Config::FASTLY && $this->config->isEnabled()) {
            $this->purgeCache->sendPurgeRequest();
        }
    }
}
