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

class SendInstalledRequestToGA implements ObserverInterface
{

    /**
     * @var Manager
     */
    protected $_moduleManager;

    /**
     * @var StatisticRepository
     */
    protected $_statisticRepo;

    /**
     * @var Statistic
     */
    protected $_statistic;

    /**
     * @var StatisticFactory
     */
    protected $_statisticFactory;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $_configWriter;

    /**
     * SendInstalledRequestToGA constructor
     *
     * @param Manager $manager
     * @param StatisticRepository $statisticRepository
     * @param Statistic $statistic
     * @param StatisticFactory $statisticFactory
     * @param Data $helper
     * @param Config $config
     * @param WriterInterface $configWriter
     */
    public function __construct(Manager $manager, StatisticRepository $statisticRepository, Statistic $statistic,
                                StatisticFactory $statisticFactory, Data $helper, Config $config, WriterInterface $configWriter)
    {
        $this->_moduleManager = $manager;
        $this->_statisticRepo = $statisticRepository;
        $this->_statistic = $statistic;
        $this->_statisticFactory = $statisticFactory;
        $this->_helper = $helper;
        $this->_config = $config;
        $this->_configWriter = $configWriter;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_moduleManager->isEnabled(Statistic::FASTLY_MODULE_NAME)) {
            $stat = $this->_statisticRepo->getStatByAction(Statistic::FASTLY_INSTALLED_FLAG);
            if($stat->getSent() == false) {
                $sendGAReq = $this->_statistic->sendInstalledReq();
                if($sendGAReq) {
                    $stat->setState(false);
                    $stat->setAction(Statistic::FASTLY_INSTALLED_FLAG);
                    $stat->setSent($sendGAReq);
                    $this->_statisticRepo->save($stat);
                }
            }
        }

        if ($this->_helper->getModuleVersion() > $this->_config->getFastlyVersion()) {
            $this->_statistic->sendUpgradeRequest();
            $this->_configWriter->save(Config::XML_FASTLY_MODULE_VERSION, $this->_helper->getModuleVersion());
        }
    }
}
