<?php

declare(strict_types=1);

namespace Fastly\Cdn\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class TlsCertificationsButton
 * @package Fastly\Cdn\Block\System\Config\Form\Field
 */
class TlsCertificationsButton extends AbstractFieldArray
{
    protected function _construct()  // @codingStandardsIgnoreLine - required by parent class
    {
        $this->addColumn('backend_name', ['label' => __('Id')]);
        $this->_addAfter = false;
        $this->_template = 'Fastly_Cdn::system/config/form/field/tlsCertificationsButton.phtml';
        parent::_construct();
    }
}
