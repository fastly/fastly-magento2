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
namespace Fastly\Cdn\Model\Layout;

use \Fastly\Cdn\Model\Config;

/**
 * Class LayoutPlugin
 *
 * @package Fastly\Cdn\Model\Layout
 */
class LayoutPlugin
{
    /**
     * @var \Magento\PageCache\Model\Config
     */
    private $config;
    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    private $response;
    /**
     * @var \Fastly\Cdn\Helper\CacheTags
     */
    private $cacheTags;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\ResponseInterface $response
     * @param \Fastly\Cdn\Model\Config $config
     * @param \Fastly\Cdn\Helper\CacheTags $cacheTags
     */
    public function __construct(
        \Magento\Framework\App\ResponseInterface $response,
        Config $config,
        \Fastly\Cdn\Helper\CacheTags $cacheTags
    ) {
        $this->response = $response;
        $this->config = $config;
        $this->cacheTags = $cacheTags;
    }

    /**
     * Set appropriate Cache-Control headers
     * Set Fastly stale headers if configured
     *
     * @param \Magento\Framework\View\Layout $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterGenerateXml(\Magento\Framework\View\Layout $subject, $result)
    {
        // if subject is cacheable, FPC cache is enabled, Fastly module is chosen and general TTL is > 0
        if ($subject->isCacheable() && $this->config->isEnabled()
            && $this->config->getType() == Config::FASTLY && $this->config->getTtl()) {
            // get cache control header
            $header = $this->response->getHeader('cache-control');
            if (($header instanceof \Zend\Http\Header\HeaderInterface) && ($value = $header->getFieldValue())) {
                // append stale values
                if ($ttl = $this->config->getStaleTtl()) {
                    $value .= ', stale-while-revalidate=' . $ttl;
                }
                if ($ttl = $this->config->getStaleErrorTtl()) {
                    $value .= ', stale-if-error=' . $ttl;
                }
                // update cache control header
                $this->response->setHeader($header->getFieldName(), $value, true);
            }
        }

        /*
         * Surface the cacheability of a page. This may expose things like page blocks being set to
         * cacheable = false which makes the whole page uncacheable
         */
        if ($subject->isCacheable()) {
            $this->response->setHeader("fastly-page-cacheable", "YES");
        } else {
            $this->response->setHeader("fastly-page-cacheable", "NO");
        }

        return $result;
    }

    /**
     * Add a debug header to indicate this request has passed through the Fastly Module.
     * This is for ease of debugging
     *
     * @param \Magento\Framework\View\Layout $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterGetOutput(\Magento\Framework\View\Layout $subject, $result) // @codingStandardsIgnoreLine - unused parameter
    {
        if ($this->config->getType() == Config::FASTLY) {
            $this->response->setHeader("Fastly-Module-Enabled", "1.2.119", true);
        }

        return $result;
    }
}
