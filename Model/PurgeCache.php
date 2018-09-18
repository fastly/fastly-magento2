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
namespace Fastly\Cdn\Model;

/**
 * Class PurgeCache
 *
 * @package Fastly\Cdn\Model
 */
class PurgeCache
{
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Config
     */
    private $config;

    /**
     * Constructor
     *
     * @param Api $api
     * @param Config $config
     */
    public function __construct(Api $api, Config $config)
    {
        $this->api = $api;
        $this->config = $config;
    }

    /**
     * Send API purge request to invalidate cache by pattern
     *
     * @param string $pattern
     * @return bool
     * @throws \Zend_Uri_Exception
     */
    public function sendPurgeRequest($pattern = '')
    {
        if (empty($pattern)) {
            if ($this->config->canPreserveStatic()) {
                $result = $this->api->cleanBySurrogateKey(['text']);
            } else {
                $result = $this->api->cleanAll();
            }
        } elseif (!is_array($pattern) && strpos($pattern, 'http') === 0) {
            $result = $this->api->cleanUrl($pattern);
        } elseif (is_array($pattern)) {
            $result = $this->api->cleanBySurrogateKey($pattern);
        } else {
            return false;
        }
        return $result;
    }
}
