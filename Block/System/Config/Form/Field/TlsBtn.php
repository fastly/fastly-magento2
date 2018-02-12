<?php

namespace Fastly\Cdn\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class TlsBtn extends Field
{
    protected function _construct() // @codingStandardsIgnoreLine - required by parent class
    {
        $this->_template = 'Fastly_Cdn::system/config/form/field/tlsBtn.phtml';

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
     * Render TLS button HTML
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
                'id' => 'fastly_force_tls_button',
                'label' => __('Force TLS'),
            ]
        );

        return $button->toHtml();
    }
}
