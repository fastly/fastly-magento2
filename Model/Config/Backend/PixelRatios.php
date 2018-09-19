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

/**
 * Class PixelRatios
 *
 * @package Fastly\Cdn\Model\Config\Backend
 */
class PixelRatios
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '1', 'label'=>__('1x')],
            ['value' => '1.5', 'label'=>__('1.5x')],
            ['value' => '2', 'label'=>__('2x')],
            ['value' => '3', 'label'=>__('3x')],
            ['value' => '3.5', 'label'=>__('3.5x')],
            ['value' => '4', 'label'=>__('4x')]
        ];
    }
}
