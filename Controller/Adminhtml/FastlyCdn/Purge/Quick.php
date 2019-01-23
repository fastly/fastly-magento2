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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Purge;

use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\PurgeCache;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Quick
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Purge
 */
class Quick extends Action
{
    /**
     * @var PurgeCache
     */
    private $purgeCache;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Config
     */
    private $config;

    /**
     * Quick constructor.
     *
     * @param Context $context
     * @param PurgeCache $purgeCache
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     */
    public function __construct(
        Context $context,
        PurgeCache $purgeCache,
        StoreManagerInterface $storeManager,
        Config $config
    ) {
        $this->purgeCache = $purgeCache;
        $this->storeManager = $storeManager;
        $this->config = $config;

        parent::__construct($context);
    }

    /**
     * Purge by content type
     *
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Exception
     */
    public function execute()
    {
        try {
            if ($this->config->getType() == Config::FASTLY && $this->config->isEnabled()) {
                // check if url is given
                $url = $this->getRequest()->getParam('quick_purge_url', false);

                $zendUri = \Zend_Uri::factory($url);
                $host = $zendUri->getHost();
                $scheme = $zendUri->getScheme();
                $path = $zendUri->getPath();

                // check if host is one of magento's
                if (!$this->isHostInDomainList($host)) {
                    throw new LocalizedException(__('Invalid domain "'.$host.'".'));
                }

                // build uri to purge
                $uri = $scheme . '://' . $host;

                if (isset($path)) {
                    $uri .= $path;
                }

                // purge uri
                $result = $this->purgeCache->sendPurgeRequest($uri);
                if ($result['status']) {
                    $this->messageManager->addSuccessMessage(__('The URL\'s "' . $url . '" cache has been cleaned.'));
                } else {
                    $this->getMessageManager()->addErrorMessage(
                        __('The purge request was not processed successfully. [' . $result['msg'] . ']')
                    );
                }
            }
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage(
                __('An error occurred while clearing the Fastly CDN: ') . $e->getMessage()
            );
        }
        return $this->_redirect('*/cache/index');
    }

    /**
     * Checks if host is one of Magento's configured domains.
     *
     * @param $host
     * @return bool
     * @throws \Zend_Uri_Exception
     */
    private function isHostInDomainList($host)
    {
        $urlTypes = [
            UrlInterface::URL_TYPE_LINK,
            UrlInterface::URL_TYPE_DIRECT_LINK,
            UrlInterface::URL_TYPE_WEB,
            UrlInterface::URL_TYPE_MEDIA,
            UrlInterface::URL_TYPE_STATIC
        ];
        $secureScheme = [true, false];

        foreach ($this->storeManager->getStores() as $store) {
            /** @var \Magento\Store\Model\Store $store */
            foreach ($urlTypes as $urlType) {
                foreach ($secureScheme as $scheme) {
                    $shopHost = \Zend_Uri::factory($store->getBaseUrl($urlType, $scheme))->getHost();
                    if ($host === $shopHost) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
