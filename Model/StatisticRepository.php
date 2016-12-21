<?php

namespace Fastly\Cdn\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Fastly\Cdn\Model\ResourceModel\Statistic as ResourceStatistic;
use Fastly\Cdn\Model\ResourceModel\Statistic\CollectionFactory as StatisticCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class BlockRepository
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StatisticRepository
{
    /**
     * @var ResourceStatistic
     */
    protected $resource;

    /**
     * @var StatisticFactory
     */
    protected $statisticFactory;

    /**
     * @var StatisticCollectionFactory
     */
    protected $statisticCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;


    public function __construct(
        ResourceStatistic $resource,
        StatisticFactory $statisticFactory,
        StatisticCollectionFactory $statisticCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->statisticFactory = $statisticFactory;
        $this->statisticCollectionFactory = $statisticCollectionFactory;
        $this->storeManager = $storeManager;
    }

    public function getStatByAction($action)
    {
        $collection = $this->statisticCollectionFactory->create();
        $collection->addFieldToFilter('action', $action);

        return $collection->getFirstItem();
    }

}
