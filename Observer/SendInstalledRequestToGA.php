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

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Manager;
use Fastly\Cdn\Model\StatisticRepository;
use Fastly\Cdn\Model\Statistic;
use Fastly\Cdn\Model\StatisticFactory;
use Fastly\Cdn\Helper\Data;
use Fastly\Cdn\Model\Config;
use \Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Class SendInstalledRequestToGA
 *
 * @package Fastly\Cdn\Observer
 */
class SendInstalledRequestToGA implements ObserverInterface
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
     * @var Data
     */
    private $helper;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * SendInstalledRequestToGA constructor
     *
     * @param Manager $manager
     * @param StatisticRepository $statisticRepository
     * @param Statistic $statistic
     * @param Data $helper
     * @param Config $config
     * @param WriterInterface $configWriter
     */
    public function __construct(
        Manager $manager,
        StatisticRepository $statisticRepository,
        Statistic $statistic,
        Data $helper,
        Config $config,
        WriterInterface $configWriter
    ) {
        $this->moduleManager = $manager;
        $this->statisticRepo = $statisticRepository;
        $this->statistic = $statistic;
        $this->helper = $helper;
        $this->config = $config;
        $this->configWriter = $configWriter;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function execute(\Magento\Framework\Event\Observer $observer) // @codingStandardsIgnoreLine - unused parameter
    {
        if ($this->moduleManager->isEnabled(Statistic::FASTLY_MODULE_NAME)) {
            $stat = $this->statisticRepo->getStatByAction(Statistic::FASTLY_INSTALLED_FLAG);
            if ($stat->getSent() == false) {
                $sendGAReq = $this->statistic->sendInstalledReq();
                if ($sendGAReq) {
                    $stat->setState(false);
                    $stat->setAction(Statistic::FASTLY_INSTALLED_FLAG);
                    $stat->setSent($sendGAReq);
                    $this->statisticRepo->save($stat);
                }
            }
        }

        if ($this->helper->getModuleVersion() > $this->config->getFastlyVersion()) {
            $this->statistic->sendUpgradeRequest();
            $this->configWriter->save(Config::XML_FASTLY_MODULE_VERSION, $this->helper->getModuleVersion());
        }
    }
}
