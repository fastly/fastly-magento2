<?php

namespace Fastly\Cdn\Model\ResourceModel\Statistic;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'stat_id';
    protected $_eventPrefix = 'fastly_cdn_statistic_collection';
    protected $_eventObject = 'statistic_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Fastly\Cdn\Model\Statistic', 'Fastly\Cdn\Model\ResourceModel\Statistic');
    }
}
