<?php

namespace Fastly\Cdn\Model\ResourceModel\Statistic;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'stat_id'; // @codingStandardsIgnoreLine - required by parent class
    protected $_eventPrefix = 'fastly_cdn_statistic_collection'; // @codingStandardsIgnoreLine - required by parent class
    protected $_eventObject = 'statistic_collection'; // @codingStandardsIgnoreLine - required by parent class

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct() // @codingStandardsIgnoreLine - required by parent class
    {
        $this->_init(
            'Fastly\Cdn\Model\Statistic',
            'Fastly\Cdn\Model\ResourceModel\Statistic'
        );
    }
}
