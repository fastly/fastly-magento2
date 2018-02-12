<?php

namespace Fastly\Cdn\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Statistic extends AbstractDb
{
    /**
     * Date model
     *
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
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object) // @codingStandardsIgnoreLine - required by parent class
    {
        $object->setCreatedAt($this->date->date());
        return parent::_beforeSave($object);
    }
}
