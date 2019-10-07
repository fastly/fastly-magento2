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

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Widget\Button;

/**
 * Class Fastly
 *
 * @package Fastly\Cdn\Block\System\Config\Form\Field\Export
 */
class Fastly extends Field
{
    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element HTML
     *
     * @param AbstractElement $element
     * @return mixed|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getElementHtml(AbstractElement $element) // @codingStandardsIgnoreLine - required by parent class
    {
        return $this->getButtonHtml();
    }

    /**
     * Return Fastly VCL export URL
     *
     * @return string
     */
    public function getExportUrl()
    {
        return $this->getUrl('adminhtml/fastlyCdn/exportVarnishConfig');
    }

    /**
     * Generate Edge button HTML
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            Button::class
        )->setData(
            [
                'id'        => 'fastly_vcl_export_button',
                'label'     => __('Download Fastly VCL'),
                'onclick'   => "setLocation('{$this->getExportUrl()}')"
            ]
        );

        return $button->toHtml();
    }
}
