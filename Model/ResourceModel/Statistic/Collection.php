<?php
/**
 * Fastly CDN for Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Fastly CDN for Magento End User License Agreement
 * that is bundled with this package in the file LICENSE_FASTLY_CDN.txt.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Fastly CDN to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Fastly
 * @package     Fastly_Cdn
 * @copyright   Copyright (c) 2016 Fastly, Inc. (http://www.fastly.com)
 * @license     BSD, see LICENSE_FASTLY_CDN.txt
 */
namespace Fastly\Cdn\Model\ResourceModel\Statistic;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @package Fastly\Cdn\Model\ResourceModel\Statistic
 */
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
