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
 * @codingStandardsIgnoreFile
 */
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn;

use Magento\PageCache\Controller\Adminhtml\PageCache\ExportVarnishConfig as ExportVarnish;

/**
 * Class ExportVarnishConfig
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn
 */
class ExportVarnishConfig extends ExportVarnish
{
    /**
     * This empty controller is necessary to inject \Fastly\Cdn\Model\Config to the parent controller using DI.
     */
}
