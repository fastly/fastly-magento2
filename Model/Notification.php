<?php

namespace Fastly\Cdn\Model;

/**
 * Fastly CDN admin notification for latest version
 */
class Notification extends \Magento\AdminNotification\Model\Feed
{

    /**
     * Github latest composer data url
     */
    CONST CHECK_VERSION_URL = 'https://raw.githubusercontent.com/fastly/fastly-magento2/master/composer.json';

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_backendAuthSession;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $_moduleList;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $_moduleManager;

    /**
     * @var CurlFactory\Magento\Framework\HTTP\Adapter\CurlFactory
     */
    protected $_curlFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $_configWriter;

    /**
     * @var \Magento\Framework\App\Cache\Manager
     */
    protected $_cacheManager;



    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\App\ConfigInterface $backendConfig
     * @param \Magento\AdminNotification\Model\InboxFactory $inboxFactory
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\Module\Manager $moduleManager,
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\ConfigInterface $backendConfig,
        \Magento\AdminNotification\Model\InboxFactory $inboxFactory,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Fastly\Cdn\Model\Config $fastlyConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $backendConfig, $inboxFactory, $curlFactory, $deploymentConfig, $productMetadata, $urlBuilder, $resource, $resourceCollection, $data);
        $this->_backendAuthSession  = $backendAuthSession;
        $this->_moduleList = $moduleList;
        $this->_moduleList = $fastlyConfig;
        $this->_scopeConfig = $scopeConfig;
        $this->_configWriter = $configWriter;
        $this->_cacheManager = $cacheManager;
        $this->_moduleManager = $moduleManager;
        $this->_curlFactory = $curlFactory;
        $this->_logger = $context->getLogger();
    }

    /**
     * Check feed for modification
     *
     * @param $currentVersion
     * @return $this
     */
    public function checkUpdate($currentVersion = null)
    {
        $lastVersion = $this->getLastVersion();
        if($lastVersion && version_compare($currentVersion, $lastVersion, '<')) {
            $versionPath = \Fastly\Cdn\Model\Config::XML_FASTLY_LAST_CHECKED_ISSUED_VERSION;
            $oldValue = $this->_scopeConfig->getValue($versionPath);
            if (version_compare($oldValue, $lastVersion, '<')) {
                $this->_configWriter->save($versionPath, $lastVersion);

                // save last version in db, and notify only if newly fetched last version is greater than stored version
                $inboxFactory = $this->_inboxFactory;
                $inbox = $inboxFactory->create();
                $inbox->addNotice('Fastly CDN', "Version $lastVersion is available. You are currently running $currentVersion. Please consider upgrading at your earliest convenience.");
                $this->_cacheManager->clean([\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER]);
            }
        }
    }

    /**
     * Fetches last github version
     *
     * @return bool|float
     */
    public function getLastVersion()
    {
        try {
            $client = $this->_curlFactory->create();
            $url = self::CHECK_VERSION_URL;

            $client->write(\Zend_Http_Client::GET, $url, '1.1');
            $responseBody = $client->read();
            $client->close();

            $responseCode = \Zend_Http_Response::extractCode($responseBody);
            if($responseCode !== 200) {
                return false;
            }
            $body = \Zend_Http_Response::extractBody($responseBody);
            $json = json_decode($body);
            $version = !empty($json->version) ? $json->version : false;

            return $version;
        } catch (\Exception $e) {
            $this->_logger->log(100, $e->getMessage().$url);
            return false;
        }
    }
}
