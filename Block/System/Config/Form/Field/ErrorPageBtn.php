<?php

namespace Fastly\Cdn\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ErrorPageBtn extends Field
{
    protected function _construct() // @codingStandardsIgnoreLine - required by parent class
    {
        $this->_template = 'Fastly_Cdn::system/config/form/field/errorPageBtn.phtml';

        parent::_construct();
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
    protected function _getElementHtml(AbstractElement $element) // @codingStandardsIgnoreLine - required by parent class
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for collect button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('adminhtml/fastlyCdn/vcl/serviceinfo');
    }

    /**
     * Generate upload button html
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
                'id' => 'fastly_error_page_button',
                'label' => __('Set HTML'),
            ]
        );

        return $button->toHtml();
    }
}
