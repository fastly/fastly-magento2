<?php

namespace Fastly\Cdn\Block\Dashboard;

class Grids extends \Magento\Backend\Block\Dashboard\Grids
{

    protected function _prepareLayout() // @codingStandardsIgnoreLine - required by parent class
    {
        parent::_prepareLayout();

        $this->addTab(
            'fastly_historic_stats',
            [
                'label' => __('Fastly'),
                'url' => $this->getUrl('adminhtml/dashboard/historic', ['_current' => true]),
                'class' => 'ajax',
                'active' => false
            ]
        );
    }
}
