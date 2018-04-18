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
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;

/**
 * This is a just a place holder to insert the ESI tag for GeoIP lookup.
 */
class GetAction extends AbstractBlock
{
    /**
     * @var Config
     */
    private $config;

    /**
     * GetAction constructor.
     *
     * @param Config $config
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Config $config,
        Context $context,
        array $data = []
    ) {
        $this->config = $config;

        parent::__construct($context, $data);
    }

    /**
     * Renders ESI GeoIp block
     *
     * @return string
     */
    protected function _toHtml() // @codingStandardsIgnoreLine - required by parent class
    {
        if ($this->config->isGeoIpEnabled() == false) {
            return parent::_toHtml();
        }

        /** @var string $actionUrl */
        $actionUrl = $this->getUrl('fastlyCdn/geoip/getaction');

        // HTTPS ESIs are not supported so we need to turn them into HTTP
        return sprintf(
            '<esi:include src=\'%s\' />',
            preg_replace("/^https/", "http", $actionUrl)
        );
    }
}
