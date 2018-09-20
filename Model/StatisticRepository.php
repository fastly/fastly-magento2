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
namespace Fastly\Cdn\Model;

use Fastly\Cdn\Model\ResourceModel\Statistic as ResourceStatistic;
use Fastly\Cdn\Model\ResourceModel\Statistic\CollectionFactory as StatisticCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class BlockRepository
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StatisticRepository
{
    /**
     * @var ResourceStatistic
     */
    private $statisticResource;
    /**
     * @var StatisticFactory
     */
    private $statisticFactory;
    /**
     * @var StatisticCollectionFactory
     */
    private $statisticCollectionFactory;
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
        /** @var \Fastly\Cdn\Model\ResourceModel\Statistic\Collection $collection */
        $collection = $this->statisticCollectionFactory->create();
        $collection->addFieldToFilter('action', $action);
        $collection->setOrder('created_at', 'DESC');

        $collection->getSelect()->limit(1); // @codingStandardsIgnoreLine - forced limit to 1
        return $collection->getFirstItem(); // @codingStandardsIgnoreLine - forced limit 1 on collection on line before
    }

    public function getValidatedNonValidated()
    {
        $collection = $this->statisticCollectionFactory->create();
        $collection->addFieldToFilter(
            'action',
            [
                ['eq' => Statistic::FASTLY_VALIDATED_FLAG],
                ['eq' => Statistic::FASTLY_NON_VALIDATED_FLAG]
            ]
        );

        return $collection->getData();
    }

    /**
     * @param Statistic $statistic
     * @return Statistic
     * @throws \Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(\Fastly\Cdn\Model\Statistic $statistic)
    {
        $this->statisticResource->save($statistic);
        return $statistic;
    }
}
