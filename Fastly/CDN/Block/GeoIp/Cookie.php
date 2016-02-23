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

namespace Fastly\CDN\Block\GeoIp;

use Fastly\CDN\Model\Config;
use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * This is a just a place holder to insert the ESI tag for GeoIP lookup.
 */
class Cookie extends \Magento\Framework\View\Element\Js\Cookie
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Config $config
     * @param ConfigInterface $cookieConfig
     * @param \Magento\Framework\Validator\Ip $ipValidator
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        ConfigInterface $cookieConfig,
        \Magento\Framework\Validator\Ip $ipValidator,
        array $data = [])
    {
        $this->config = $config;
        parent::__construct($context, $cookieConfig, $ipValidator, $data);
    }

    /**
     * @return bool
     */
    public function isGeoIpEnabled()
    {
        return $this->config->isGeoIpEnabled();
    }

    /**
     * @return string
     */
    public function getGeoIpCookieName()
    {
        return Config::GEOIP_PROCESSED_COOKIE_NAME;
    }
}