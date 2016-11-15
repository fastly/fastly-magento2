<?php
namespace Fastly\Cdn\Block\System\Config\Form\Field\Export;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Fastly extends Field
{
    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

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
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
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
     * Generate upload button html
     *
     * @return string
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
