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
 */
class LayoutPlugin
{
    /**
     * @var \Magento\PageCache\Model\Config
     */
    protected $config;

    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $response;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\ResponseInterface $response
     * @param \Fastly\Cdn\Model\Config $config
     */
    public function __construct(
        \Magento\Framework\App\ResponseInterface $response,
        Config $config
    ) {
        $this->response = $response;
        $this->config = $config;
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
        return $result;
    }

    /**
     * Adjust X-Magento-Tags for Fastly
     *
     * @param \Magento\Framework\View\Layout $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterGetOutput(\Magento\Framework\View\Layout $subject, $result)
    {
        if ($this->config->getType() == Config::FASTLY) {
            // Fastly expects surrogate keys separated by space. replace existing header.
            $header = $this->response->getHeader('X-Magento-Tags');
            if ($header instanceof \Zend\Http\Header\HeaderInterface) {
                $this->response->setHeader($header->getFieldName(), str_replace(',', ' ', $header->getFieldValue()), true);
            }
        }
        return $result;
    }
}
