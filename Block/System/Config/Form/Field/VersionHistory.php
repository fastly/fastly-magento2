<?php

namespace Fastly\Cdn\Block\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class VersionHistory
 * @package Fastly\Cdn\Block\System\Config\Form\Field
 */
class VersionHistory extends AbstractFieldArray
{

    protected function _construct()  // @codingStandardsIgnoreLine - required by parent class
    {
        $this->addColumn('backend_name', ['label' => __('Id')]);
        $this->_addAfter = false;
        $this->_template = 'Fastly_Cdn::system/config/form/field/versionHistory.phtml';
        parent::_construct();
    }
}
