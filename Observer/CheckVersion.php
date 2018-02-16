<?php

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
 * Fastly CDN observer for new version notification
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
