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
     * AdminLoginSucceededObserver constructor.
     * @param Manager $manager
     * @param StatisticRepository $statisticRepository
     * @param Statistic $statistic
     * @param StatisticFactory $statisticFactory
     */
    public function __construct(Manager $manager, StatisticRepository $statisticRepository, Statistic $statistic,
                                StatisticFactory $statisticFactory)
    {
        $this->_moduleManager = $manager;
        $this->_statisticRepo = $statisticRepository;
        $this->_statistic = $statistic;
        $this->_statisticFactory = $statisticFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if($this->_moduleManager->isEnabled(Statistic::FASTLY_MODULE_NAME)) {
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
    }
}
