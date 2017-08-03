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

namespace Fastly\Cdn\Block\GeoIp;

use Fastly\Cdn\Model\Config;

/**
 * This is a just a place holder to insert the ESI tag for GeoIP lookup.
 */
class GetAction extends \Magento\Framework\View\Element\AbstractBlock
{
    /**
     * @var Config
     */
    protected $_config;

    /**
     * GetAction constructor.
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(\Magento\Framework\View\Element\Context $context, Config $config, array $data = [])
    {
        $this->_config = $config;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->_config->isGeoIpEnabled()) {
            # https ESIs are not supported so we need to turn them into http
            return sprintf('<esi:include src=\'%s\' />', preg_replace("/^https/", "http", $this->getUrl('fastlyCdn/geoip/getaction')));
        }
        return parent::_toHtml();
    }
}
