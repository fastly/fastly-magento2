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

use Fastly\Cdn\Model\PurgeCache;
use Fastly\Cdn\Model\Config;
use Magento\Framework\UrlInterface;

class Quick extends \Magento\Backend\App\Action
{
    /**
     * @var PurgeCache
     */
    protected $purgeCache;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param PurgeCache $purgeCache
     * @param \Magento\Store\Model\StoreManagerInterface
     * @param Config $config
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        PurgeCache $purgeCache,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Config $config
    ) {
        parent::__construct($context);
        $this->purgeCache = $purgeCache;
        $this->storeManager = $storeManager;
        $this->config = $config;
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
                $urlFragments = parse_url($url);

                if (!$url || $urlFragments === false) {
                    throw new \Exception(__('Invalid URL "'.$url.'".'));
                }

                // get url fragments
                extract($urlFragments);

                // check if host is set
                if (!isset($host) || !isset($scheme)) {
                    throw new \Exception(__('Invalid URL "'.$url.'".'));
                }

                // check if host is one of magento's
                if (!$this->isHostInDomainList($host)) {
                    throw new \Exception(__('Invalid domain "'.$host.'".'));
                }

                // build uri to purge
                $uri = $scheme . '://' . $host;

                if (isset($path)) {
                    $uri .= $path;
                }
                if (isset($query)) {
                    $uri .= '\?';
                    $uri .= $query;
                }
                if (isset($fragment)) {
                    $uri .= '#';
                    $uri .= $fragment;
                }

                // purge uri
                $result = $this->purgeCache->sendPurgeRequest($uri);
                if ($result) {
                    $this->messageManager->addSuccessMessage(__('The URL\'s "' . $url . '" cache has been cleaned.'));
                } else {
                    $this->getMessageManager()->addErrorMessage(
                        __('The purge request was not processed successfully.')
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
     * @param string $host
     * @return bool
     */
    protected function isHostInDomainList($host)
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
            /* @var $store \Magento\Store\Model\Store */
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