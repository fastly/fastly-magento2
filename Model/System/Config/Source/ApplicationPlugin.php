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
namespace Fastly\Cdn\Model\System\Config\Source;

use Magento\PageCache\Model\System\Config\Source\Application;
use Fastly\Cdn\Model\Config;

/**
 * Class ApplicationPlugin
 *
 * @package Fastly\Cdn\Model\System\Config\Source
 */
class ApplicationPlugin
{
    /**
     * @param Application $application
     * @param array $optionArray
     * @return array
     */
    public function afterToOptionArray(Application $application, array $optionArray) // @codingStandardsIgnoreLine - unused parameter
    {
        return array_merge($optionArray, [['value' => Config::FASTLY, 'label' => __('Fastly CDN')]]);
    }

    /**
     * @param Application $application
     * @param array $optionArray
     * @return array
     */
    public function afterToArray(Application $application, array $optionArray) // @codingStandardsIgnoreLine - unused parameter
    {
        $optionArray[Config::FASTLY] = __('Fastly CDN');
        return $optionArray;
    }
}
