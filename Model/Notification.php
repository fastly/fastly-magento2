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

use Magento\AdminNotification\Model\Feed;
use Magento\AdminNotification\Model\InboxFactory;
use Magento\Backend\App\ConfigInterface;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;

/**
 * Class Notification
 *
 * Fastly CDN admin notification for latest version
 * @package Fastly\Cdn\Model
 */
class Notification extends Feed
{
    /**
     * Github latest composer data url
     */
    const CHECK_VERSION_URL = 'https://raw.githubusercontent.com/fastly/fastly-magento2/master/composer.json';
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var WriterInterface
     */
    private $configWriter;
    /**
     * @var Manager
     */
    private $cacheManager;

    /**
     * Notification constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param ConfigInterface $backendConfig
     * @param InboxFactory $inboxFactory
     * @param CurlFactory $curlFactory
     * @param DeploymentConfig $deploymentConfig
     * @param ProductMetadataInterface $productMetadata
     * @param UrlInterface $urlBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param Manager $cacheManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ConfigInterface $backendConfig,
        InboxFactory $inboxFactory,
        CurlFactory $curlFactory,
        DeploymentConfig $deploymentConfig,
        ProductMetadataInterface $productMetadata,
        UrlInterface $urlBuilder,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        Manager $cacheManager,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->cacheManager = $cacheManager;
        $this->_logger = $context->getLogger();

        parent::__construct(
            $context,
            $registry,
            $backendConfig,
            $inboxFactory,
            $curlFactory,
            $deploymentConfig,
            $productMetadata,
            $urlBuilder,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Check feed for modification
     *
     * @param null $currentVersion
     */
    public function checkUpdate($currentVersion = null)
    {
        $lastVersion = $this->getLastVersion();

        if (!$lastVersion || version_compare($lastVersion, $currentVersion, '<=')) {
            return;
        }

        $versionPath = Config::XML_FASTLY_LAST_CHECKED_ISSUED_VERSION;
        $oldValue = $this->scopeConfig->getValue($versionPath);

        if (version_compare($oldValue, $lastVersion, '<')) {
            $this->configWriter->save($versionPath, $lastVersion);

            // save last version in db, and notify only if newly fetched last version is greater than stored version
            $inboxFactory = $this->_inboxFactory;
            $inbox = $inboxFactory->create();
            $inbox->addNotice(
                'Fastly CDN',
                "Version $lastVersion is available. You are currently running $currentVersion."
                    . ' Please consider upgrading at your earliest convenience.'
            );
            $this->cacheManager->clean([\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER]);
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
            $url = self::CHECK_VERSION_URL;
            $client = $this->curlFactory->create();

            $client->write(\Zend_Http_Client::GET, $url, '1.1');
            $responseBody = $client->read();
            $client->close();

            $responseCode = \Zend_Http_Response::extractCode($responseBody);
            if ($responseCode !== 200) {
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
