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
namespace Fastly\Cdn\Model\System\Config\Source\GeoIP;

/**
 * Class Action
 *
 * @package Fastly\Cdn\Model\System\Config\Source\GeoIP
 */
class Action implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => \Fastly\Cdn\Model\Config::GEOIP_ACTION_DIALOG,
                'label' => __('Dialog'),
            ],
            [
                'value' => \Fastly\Cdn\Model\Config::GEOIP_ACTION_REDIRECT,
                'label' => __('Redirect')
            ]
        ];
    }
}
