<?php

namespace Fastly\Cdn\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Manifest extends AbstractDb
{
    protected $_isPkAutoIncrement = false; // @codingStandardsIgnoreLine - required by parent class
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct() // @codingStandardsIgnoreLine - required by parent class
    {
        $this->_init('fastly_modly_manifests', 'manifest_id');
    }
}
