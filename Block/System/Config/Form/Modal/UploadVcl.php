<?php

namespace Fastly\Cdn\Block\System\Config\Form\Modal;

class UploadVcl extends \Magento\Backend\Block\Template
{
    protected function _prepareLayout() // @codingStandardsIgnoreLine - required by parent class
    {
        parent::_prepareLayout();

        $this->addChild('dialogs', 'Fastly\Cdn\Block\System\Config\Form\Dialogs');
    }

    public function getDialogsHtml()
    {
        return $this->getChildHtml('dialogs');
    }

    public function _toHtml()
    {
        return $this->getDialogsHtml();
    }
}
