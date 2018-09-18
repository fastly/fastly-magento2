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

use Fastly\Cdn\Model\Notification;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Model\Context;

/**
 * Class CheckVersion
 *
 * Fastly CDN observer for new version notification
 * @package Fastly\Cdn\Observer
 */
class CheckVersion implements ObserverInterface
{
    /**
     * @var Notification
     */
    private $feedFactory;
    /**
     * @var Session
     */
    private $backendAuthSession;
    /**
     * @var ComponentRegistrarInterface
     */
    private $moduleRegistry;
    /**
     * @var CacheInterface
     */
    private $cacheManager;

    /**
     * CheckVersion constructor.
     *
     * @param Notification $feedFactory
     * @param Session $backendAuthSession
     * @param Context $context
     * @param ComponentRegistrarInterface $moduleRegistry
     */
    public function __construct(
        Notification $feedFactory,
        Session $backendAuthSession,
        Context $context,
        ComponentRegistrarInterface $moduleRegistry
    ) {
        $this->moduleRegistry = $moduleRegistry;
        $this->backendAuthSession = $backendAuthSession;
        $this->feedFactory = $feedFactory;
        $this->cacheManager = $context->getCacheManager();
    }

    /**
     * Predispatch admin user login success
     *
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer) // @codingStandardsIgnoreLine - unused parameter
    {
        if ($this->backendAuthSession->isLoggedIn() == false) {
            return;
        }

        if ($this->getFrequency() + $this->getLastUpdate() > time()) {
            return;
        }

        $modulePath = $this->moduleRegistry->getPath(ComponentRegistrar::MODULE, 'Fastly_Cdn');
        $filePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, "$modulePath/composer.json");
        $composerData = json_decode(file_get_contents($filePath)); // @codingStandardsIgnoreLine - not user controlled
        $currentVersion = !empty($composerData->version) ? $composerData->version : false;

        if ($currentVersion) {
            $this->feedFactory->checkUpdate($currentVersion);
        }

        $this->setLastUpdate();
    }

    /**
     * Retrieve Last update time
     *
     * @return int
     */
    private function getLastUpdate()
    {
        return $this->cacheManager->load('fastlycdn_admin_notifications_lastcheck');
    }

    /**
     * Set last update time (now)
     *
     * @return $this
     */
    private function setLastUpdate()
    {
        $this->cacheManager->save(time(), 'fastlycdn_admin_notifications_lastcheck');
        return $this;
    }

    /**
     * Retrieve Update Frequency
     *
     * @return int
     */
    private function getFrequency()
    {
        return 86400;
    }
}
