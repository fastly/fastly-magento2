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
namespace Fastly\Cdn\Plugin;

use Magento\Framework\View\Asset\Minification;

/**
 * Class ExcludeFilesFromMinification
 *
 * @package Fastly\Cdn\Plugin
 */
class ExcludeFilesFromMinification
{
    /**
     * @param Minification $subject
     * @param callable $proceed
     * @param $contentType
     * @return array
     */
    public function aroundGetExcludes(Minification $subject, callable $proceed, $contentType)
    {
        $result = $proceed($contentType);
        if ($contentType != 'js' && !$subject->isEnabled($contentType)) {
            return $result;
        }
        $result[] = 'https://www.gstatic.com/charts/loader.js';
        return $result;
    }
}
