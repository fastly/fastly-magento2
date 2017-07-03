<?php

namespace Fastly\Cdn\Observer;

use Magento\Framework\Event\ObserverInterface;
use Fastly\Cdn\Model\Notification;
use Magento\Framework\Component\ComponentRegistrar;


/**
 * Fastly CDN observer for new version notification
 */
class CheckVersion implements ObserverInterface
{
    /**
     * @var \Fastly\Cdn\Model\Notification
     */
    protected $_feedFactory;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_backendAuthSession;


    protected $_moduleRegistry;
    /**
     * @param \Fastly\Cdn\Model\Notification $feedFactory
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     */
    public function __construct(
        \Fastly\Cdn\Model\Notification $feedFactory,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Component\ComponentRegistrarInterface $moduleRegistry
    ) {
        $this->_moduleRegistry = $moduleRegistry;
        $this->_backendAuthSession = $backendAuthSession;
        $this->_feedFactory = $feedFactory;
        $this->_cacheManager = $context->getCacheManager();
    }

    /**
     * Predispatch admin user login success
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_backendAuthSession->isLoggedIn()) {

            if ($this->getFrequency() + $this->getLastUpdate() > time()) {
                return $this;
            }

            $modulePath = $this->_moduleRegistry->getPath(ComponentRegistrar::MODULE, 'Fastly_Cdn');
            $filePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, "$modulePath/composer.json");
            $composerData = json_decode(file_get_contents($filePath));
            $currentVersion = !empty($composerData->version) ? $composerData->version : false;

            if($currentVersion) {
                $this->_feedFactory->checkUpdate($currentVersion);
            }

            $this->setLastUpdate();
        }
    }

    /**
     * Retrieve Last update time
     *
     * @return int
     */
    public function getLastUpdate()
    {
        return $this->_cacheManager->load('fastlycdn_admin_notifications_lastcheck');
    }

    /**
     * Set last update time (now)
     *
     * @return $this
     */
    public function setLastUpdate()
    {
        $this->_cacheManager->save(time(), 'fastlycdn_admin_notifications_lastcheck');
        return $this;
    }

    /**
     * Retrieve Update Frequency
     *
     * @return int
     */
    public function getFrequency()
    {
        return 86400;
    }
}
