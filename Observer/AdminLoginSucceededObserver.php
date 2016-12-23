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

class AdminLoginSucceededObserver implements ObserverInterface
{

    protected $_moduleManager;

    protected $_statisticRepo;

    protected $_statistic;

    public function __construct(Manager $manager, StatisticRepository $statisticRepository, Statistic $statistic)
    {
        $this->_moduleManager = $manager;
        $this->_statisticRepo = $statisticRepository;
        $this->_statistic = $statistic;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if($this->_moduleManager->isEnabled(Statistic::FASTLY_MODULE_NAME)) {
            $stat = $this->_statisticRepo->getStatByAction(Statistic::FASTLY_INSTALLED_FLAG);
            if(!is_null($stat)) {
                if($stat->getStatus() == false) {
                  $this->_statistic->sendInstalledReq();
                }
            } else {
                $this->_statisticRepo->create();
            }
        }
    }
}
