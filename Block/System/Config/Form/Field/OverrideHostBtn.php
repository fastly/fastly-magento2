<?php

namespace Fastly\Cdn\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class OverrideHostBtn
 * @package Fastly\Cdn\Block\System\Config\Form\Field
 */
class OverrideHostBtn extends AbstractFieldArray
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('backend_name', ['label' => __('Id')]);
        $this->_addAfter = false;
        $this->_template = 'Fastly_Cdn::system/config/form/field/overrideHost.phtml';
        parent::_construct();
    }
}
