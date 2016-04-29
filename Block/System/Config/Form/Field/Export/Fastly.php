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
namespace Fastly\Cdn\Block\System\Config\Form\Field\Export;

use \Fastly\Cdn\Model\Config;

/**
 * Class Export
 */
class Fastly extends \Magento\PageCache\Block\System\Config\Form\Field\Export
{
    /**
     * Return Varnish version to this class
     *
     * @return int
     */
    public function getVarnishVersion()
    {
        return Config::FASTLY;
    }

    /**
     * @inheritdoc
     */
    public function getUrl($route = '', $params = [])
    {
        if (strpos($route, 'PageCache/exportVarnishConfig')) {
            $route = '*/FastlyCdn/exportVarnishConfig';
        }
        return parent::getUrl($route, $params);
    }

    /**
     * Not used. Requires core change
     *
     * @return \Magento\Framework\Phrase
     */
    protected function _getLabel()
    {
        return __('Export VCL for Fastly');
    }

    /**
     * Not used. Requires core change
     *
     * @param array $params
     * @return string
     */
    protected function _getUrl($params = [])
    {
        return $this->getUrl('*/FastlyCdn/exportVarnishConfig', $params);
    }
}
