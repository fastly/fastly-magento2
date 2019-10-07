<?php

namespace Fastly\Cdn\Model\ResourceModel\Manifest;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Fastly\Cdn\Model\ResourceModel\Manifest;
use Fastly\Cdn\Model\Manifest as ManifestModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'manifest_id'; // @codingStandardsIgnoreLine - required by parent class
    protected $_eventPrefix = 'fastly_cdn_manifest_collection'; // @codingStandardsIgnoreLine - required by parent class
    protected $_eventObject = 'manifest_collection'; // @codingStandardsIgnoreLine - required by parent class

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct() // @codingStandardsIgnoreLine - required by parent class
    {
        $this->_init(
            ManifestModel::class,
            Manifest::class
        );
    }
}
