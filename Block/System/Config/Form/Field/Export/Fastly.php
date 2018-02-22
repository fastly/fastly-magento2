<?php

namespace Fastly\Cdn\Block\System\Config\Form\Field\Export;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

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
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'fastly_vcl_export_button',
                'label' => __('Download Fastly VCL'),
                'onclick' => "setLocation('{$this->getExportUrl()}')"
            ]
        );

        return $button->toHtml();
    }
}
