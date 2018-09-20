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
namespace Fastly\Cdn\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Class Statistic
 *
 * @package Fastly\Cdn\Model\ResourceModel
 */
class Statistic extends AbstractDb
{
    /**
     * @var DateTime
     */
    private $date;

    /**
     * constructor
     *
     * @param DateTime $date
     * @param Context $context
     */
    public function __construct(
        DateTime $date,
        Context $context
    ) {
        $this->date = $date;

        parent::__construct($context);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct() // @codingStandardsIgnoreLine - required by parent class
    {
        $this->_init('fastly_statistics', 'stat_id');
    }

    /**
     * @param AbstractModel $object
     * @return AbstractDb
     */
    protected function _beforeSave(AbstractModel $object) // @codingStandardsIgnoreLine - required by parent class
    {
        $object->setCreatedAt($this->date->date());
        return parent::_beforeSave($object);
    }
}
