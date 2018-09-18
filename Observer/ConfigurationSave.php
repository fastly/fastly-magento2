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
namespace Fastly\Cdn\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Manager;
use Fastly\Cdn\Model\StatisticRepository;
use Fastly\Cdn\Model\Statistic;
use Fastly\Cdn\Model\StatisticFactory;

/**
 * Class ConfigurationSave
 *
 * @package Fastly\Cdn\Observer
 */
class ConfigurationSave implements ObserverInterface
{
    /**
     * @var Manager
     */
    private $moduleManager;
    /**
     * @var StatisticRepository
     */
    private $statisticRepo;
    /**
     * @var Statistic
     */
    private $statistic;
    /**
     * @var StatisticFactory
     */
    private $statisticFactory;

    /**
     * ConfigurationSave constructor.
     * @param Manager $manager
     * @param StatisticRepository $statisticRepository
     * @param Statistic $statistic
     * @param StatisticFactory $statisticFactory
     */
    public function __construct(
        Manager $manager,
        StatisticRepository $statisticRepository,
        Statistic $statistic,
        StatisticFactory $statisticFactory
    ) {
        $this->moduleManager = $manager;
        $this->statisticRepo = $statisticRepository;
        $this->statistic = $statistic;
        $this->statisticFactory = $statisticFactory;
    }

    /**
     * @param Observer $observer
     * @throws \Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function execute(Observer $observer) // @codingStandardsIgnoreLine - unused parameter
    {
        if ($this->moduleManager->isEnabled(Statistic::FASTLY_MODULE_NAME) == false) {
            return;
        }

        $isServiceValid = $this->statistic->isApiKeyValid();
        $stat = $this->statisticRepo->getStatByAction(Statistic::FASTLY_CONFIGURATION_FLAG);

        if ((!$stat->getId()) || !($stat->getState() == true && $isServiceValid == true)) {
            $GAreq = $this->statistic->sendConfigurationRequest($isServiceValid);
            $newConfigured = $this->statisticFactory->create();
            $newConfigured->setAction(Statistic::FASTLY_CONFIGURATION_FLAG);
            $newConfigured->setState($isServiceValid);
            $newConfigured->setSent($GAreq);
            $this->statisticRepo->save($newConfigured);
        }
    }
}
