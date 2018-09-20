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
namespace Fastly\Cdn\Model\PageCache;

use Fastly\Cdn\Model\Config;
use Magento\CacheInvalidate\Model\PurgeCache;

/**
 * Class PurgeCachePlugin
 *
 * @package Fastly\Cdn\Model\PageCache
 */
class PurgeCachePlugin
{
    /**
     * @var Config
     */
    private $config;

    /**
     * PurgeCachePlugin constructor.
     *
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Prevent Magento from executing purge requests on Varnish when Fastly is enabled
     *
     * @param PurgeCache $subject
     * @param callable $proceed
     * @param array ...$args
     */
    public function aroundSendPurgeRequest(PurgeCache $subject, callable $proceed, ...$args) // @codingStandardsIgnoreLine - unused parameter
    {
        if ($this->config->isFastlyEnabled() !== true) {
            $proceed(...$args);
        }
    }
}
