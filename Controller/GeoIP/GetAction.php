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
namespace Fastly\Cdn\Controller\GeoIP;

use Fastly\Cdn\Model\Config;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Locale\ResolverInterface as LocaleResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\Layout;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Action\Action;

/**
 * Class GetAction
 *
 * @package Fastly\Cdn\Controller\GeoIP
 */
class GetAction extends Action
{
    const REQUEST_PARAM_COUNTRY = 'country_code';
    /**
     * @var Config
     */
    private $config;
    /**
     * @var UrlInterface
     */
    private $url;
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var LayoutFactory
     */
    private $resultLayoutFactory;
    /**
     * @var LocaleResolverInterface
     */
    private $localeResolver;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * GetAction constructor.
     * @param Context $context
     * @param Config $config
     * @param StoreRepositoryInterface $storeRepository
     * @param StoreManagerInterface $storeManager
     * @param LayoutFactory $resultLayoutFactory
     * @param LocaleResolverInterface $localeResolver
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Config $config,
        StoreRepositoryInterface $storeRepository,
        StoreManagerInterface $storeManager,
        LayoutFactory $resultLayoutFactory,
        LocaleResolverInterface $localeResolver,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->config               = $config;
        $this->storeRepository      = $storeRepository;
        $this->storeManager         = $storeManager;
        $this->resultLayoutFactory  = $resultLayoutFactory;
        $this->localeResolver       = $localeResolver;
        $this->logger               = $logger;

        $this->url  = $context->getUrl();
    }

    /**
     * Get GeoIP action
     *
     * @return ResponseInterface|ResultInterface|Layout|null
     */
    public function execute()
    {
        $resultLayout = null;

        try {
            $resultLayout = $this->resultLayoutFactory->create();
            $resultLayout->addDefaultHandle();

            // get target store from country code
            $countryCode = $this->getRequest()->getParam(self::REQUEST_PARAM_COUNTRY);
            $storeId = $this->config->getGeoIpMappingForCountry($countryCode);
            $targetUrl = $this->getRequest()->getParam('uenc');

            if ($storeId !== null) {
                // get redirect URL
                $redirectUrl = null;
                $targetStore = $this->storeRepository->getActiveStoreById($storeId);
                $currentStore = $this->storeManager->getStore();
                // only generate a redirect URL if current and new store are different
                if ($currentStore->getId() != $targetStore->getId()) {
                    $this->url->setScope($targetStore->getId());

                    $queryParams = [
                        '___store'      => $targetStore->getCode(),
                        '___from_store' => $currentStore->getCode()
                    ];
                    if ($targetUrl) {
                        array_push($queryParams, ['uenc' => $targetUrl]);
                    }
                    $this->url->addQueryParams($queryParams);
                    $redirectUrl = $this->url->getUrl('stores/store/switch');
                }

                // generate output only if redirect should be performed
                if ($redirectUrl) {
                    switch ($this->config->getGeoIpAction()) {
                        case Config::GEOIP_ACTION_DIALOG:
                            $resultLayout->getLayout()->getUpdate()->load(['geoip_getaction_dialog']);
                            $resultLayout->getLayout()->getBlock('geoip_getaction')->setMessage(
                                $this->getMessageInStoreLocale($targetStore)
                            );
                            break;
                        case Config::GEOIP_ACTION_REDIRECT:
                            $resultLayout->getLayout()->getUpdate()->load(['geoip_getaction_redirect']);
                            break;
                    }

                    $resultLayout->getLayout()->getBlock('geoip_getaction')->setRedirectUrl($redirectUrl);
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            // do not generate output on errors. this is similar to an empty GeoIP mapping for the country.
        }

        $resultLayout->setHeader("x-esi", "1");
        return $resultLayout;
    }

    /**
     * Gets the dialog message in the locale of the target store.
     * @param StoreInterface $emulatedStore
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getMessageInStoreLocale(StoreInterface $emulatedStore)
    {
        $currentStore = $this->storeManager->getStore();

        // emulate locale and store of new store to fetch message translation
        $this->localeResolver->emulate($emulatedStore->getId());
        $this->storeManager->setCurrentStore($emulatedStore->getId());

        $message = __(
            'You are in the wrong store. Click OK to visit the %1 store.',
            [$emulatedStore->getName()]
        )->__toString();

        // revert locale and store emulation
        $this->localeResolver->revert();
        $this->storeManager->setCurrentStore($currentStore->getId());

        return $message;
    }
}
