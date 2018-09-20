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
namespace Fastly\Cdn\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;

/**
 * Class Geoipcountry
 *
 * Extending GeoIp to handle serialization/json format difference between M2.2 and above vs prior versions
 * @package Fastly\Cdn\Model\Config\Backend
 */
class Geoipcountry extends ArraySerialized
{
    protected function _afterLoad() // @codingStandardsIgnoreLine - required by parent class
    {
        $value = $this->getValue();

        $oldData = json_decode($value, true);
        if (!$oldData) {
            try {
                $oldData = unserialize($value); // @codingStandardsIgnoreLine - fallback to prior magento versions
            } catch (\Exception $e) {
                $oldData = false;
            }
        }
        if ($oldData) {
            $oldData = (is_array($oldData)) ? $oldData : [];
            $this->setValue(empty($oldData) ? false : $oldData);
        } else {
            $this->setValue(empty($value) ? false : json_decode($value, true));
        }
    }
}
