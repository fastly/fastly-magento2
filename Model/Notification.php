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

use Laminas\Http\ClientFactory;
use Laminas\Http\Request;
use Laminas\Http\RequestFactory;
use Magento\AdminNotification\Model\InboxFactory;
use Magento\Backend\App\ConfigInterface;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;

/**
 * Class Notification
 *
 * Fastly CDN admin notification for latest version
 */
class Notification extends AbstractModel
{
    /**
     * Github latest composer data url
     */
    public const CHECK_VERSION_URL = 'https://raw.githubusercontent.com/fastly/fastly-magento2/master/composer.json';
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
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var InboxFactory
     */
    private $inboxFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param Manager $cacheManager
     * @param ClientFactory $clientFactory
     * @param RequestFactory $requestFactory
     * @param InboxFactory $inboxFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context              $context,
        Registry             $registry,
        ScopeConfigInterface $scopeConfig,
        WriterInterface      $configWriter,
        Manager              $cacheManager,
        ClientFactory        $clientFactory,
        RequestFactory       $requestFactory,
        InboxFactory         $inboxFactory,
        ?AbstractResource    $resource = null,
        ?AbstractDb          $resourceCollection = null,
        array                $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->cacheManager = $cacheManager;
        $this->_logger = $context->getLogger();
        $this->clientFactory = $clientFactory;
        $this->requestFactory = $requestFactory;
        $this->inboxFactory = $inboxFactory;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Check latest version and set inbox notice
     *
     * @param string $currentVersion
     * @return void
     */
    public function checkUpdate(string $currentVersion): void
    {
        $lastVersion = $this->getLastVersion();

        if (!$lastVersion || version_compare($lastVersion, $currentVersion, '<=')) {
            return;
        }

        $versionPath = Config::XML_FASTLY_LAST_CHECKED_ISSUED_VERSION;
        $oldValue = (string)$this->scopeConfig->getValue($versionPath);

        if (version_compare($oldValue, $lastVersion, '<')) {
            $this->configWriter->save($versionPath, $lastVersion);
            $inbox = $this->inboxFactory->create();
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
     * @return string
     */
    public function getLastVersion(): string
    {
        try {
            $url = self::CHECK_VERSION_URL;
            $client = $this->clientFactory->create();
            $request = $this->requestFactory->create();
            $request->setMethod(Request::METHOD_GET);
            $request->setUri($url);
            $response = $client->send($request);

            $responseCode = $response->getStatusCode();
            if ($responseCode !== 200) {
                return '';
            }
            $body = $response->getBody();
            $json = json_decode($body);
            return !empty($json->version) ? $json->version : '';
        } catch (\Exception $e) {
            $this->_logger->log(100, $e->getMessage() . $url);
            return '';
        }
    }
}
