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

use \Magento\PageCache\Model\Config;

/**
 * Model is responsible for replacing default vcl template
 * file configuration with user-defined from configuration
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */

/**
 * Class ConfigPlugin
 *
 * @package Fastly\Cdn\Model\PageCache
 */
class ConfigPlugin
{
    protected $_scopeConfig;

    /**
     * ConfigPlugin constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Return cache type "Varnish" if Fastly is configured.
     *
     * @param Config $config
     * @param string $result
     *
     * @return int
     */
    public function afterGetType(Config $config, $result)
    {
        if (!($config instanceof \Fastly\Cdn\Model\Config)) {
            if ($result == \Fastly\Cdn\Model\Config::FASTLY) {
                return Config::VARNISH;
            }
        }
        return $result;
    }

    /**
     * Change return value from string to int, 'fastly' is old value
     *
     * @param Config $config
     * @param callable $proceed
     * @return string|int
     */
    public function aroundGetType(Config $config, callable $proceed)
    {
        if ($this->_scopeConfig->getValue(Config::XML_PAGECACHE_TYPE) === 'fastly') {
            $result = \Fastly\Cdn\Model\Config::FASTLY;
        } else {
            $result = (int)$proceed();
        }
        return $result;
    }
}
