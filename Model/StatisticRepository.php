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
    protected $statisticResource;

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

    /**
     * StatisticRepository constructor.
     * @param ResourceStatistic $statisticResource
     * @param StatisticFactory $statisticFactory
     * @param StatisticCollectionFactory $statisticCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceStatistic $statisticResource,
        StatisticFactory $statisticFactory,
        StatisticCollectionFactory $statisticCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->statisticResource = $statisticResource;
        $this->statisticFactory = $statisticFactory;
        $this->statisticCollectionFactory = $statisticCollectionFactory;
        $this->storeManager = $storeManager;
    }

    public function getStatByAction($action)
    {
        $collection = $this->statisticCollectionFactory->create();
        $collection->addFieldToFilter('action', $action);
        $collection->setOrder('created_at', 'DESC');

        return $collection->getFirstItem();
    }

    public function getValidatedNonValidated()
    {
        $collection = $this->statisticCollectionFactory->create();
        $collection->addFieldToFilter('action', [['eq' => Statistic::FASTLY_VALIDATED_FLAG],
                                            ['eq' => Statistic::FASTLY_NON_VALIDATED_FLAG]]);

        return $collection->getData();
    }

    public function save(\Fastly\Cdn\Model\Statistic $statistic)
    {
        $this->statisticResource->save($statistic);
        return $statistic;
    }

}
