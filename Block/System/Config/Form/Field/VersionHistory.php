<?php

namespace Fastly\Cdn\Block\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class VersionHistory extends AbstractFieldArray
{
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->addColumn('backend_name', ['label' => __('Version')]);
        $this->_addAfter = false;
        $this->_template = 'Fastly_Cdn::system/config/form/field/versionHistory.phtml';
        parent::_construct();
    }

    public function renderCellTemplate($columnName)
    {
        return parent::renderCellTemplate($columnName);
    }
}
