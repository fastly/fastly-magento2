<?php

namespace Fastly\Cdn\Block\Dashboard\Tab\Stats;

use Magento\Backend\Block\Template;

class Historic extends Template
{
    protected function _construct() // @codingStandardsIgnoreLine - required by parent class
    {
        $this->_template = 'Fastly_Cdn::dashboard/stats/historic.phtml';

        parent::_construct();
    }
}
