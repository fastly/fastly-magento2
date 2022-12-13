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

use Fastly\Cdn\Helper\StoreMessage;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Resolver\GeoIP\CountryCodeProviderInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Url\DecoderInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\Layout;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class GetAction for esi hole
 *
 */
class GetAction extends Action
{
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
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var StoreMessage
     */
    private $storeMessage;
    /**
     * @var EncoderInterface
     */
    private $urlEncoder;
    /**
     * @var DecoderInterface
     */
    private $urlDecoder;

    /**
     * @var CountryCodeProviderInterface
     */
    protected $countryCodeProvider;

    /**
     * GetAction constructor.
     * @param Context $context
     * @param Config $config
     * @param StoreRepositoryInterface $storeRepository
     * @param StoreManagerInterface $storeManager
     * @param LayoutFactory $resultLayoutFactory
     * @param LoggerInterface $logger
     * @param StoreMessage $storeMessage
     * @param EncoderInterface $urlEncoder
     * @param DecoderInterface $urlDecoder
     * @param CountryCodeProviderInterface $countryCodeProvider
     */
    public function __construct(
        Context $context,
        Config $config,
        StoreRepositoryInterface $storeRepository,
        StoreManagerInterface $storeManager,
        LayoutFactory $resultLayoutFactory,
        LoggerInterface $logger,
        StoreMessage $storeMessage,
        EncoderInterface $urlEncoder,
        DecoderInterface $urlDecoder,
        CountryCodeProviderInterface $countryCodeProvider
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->storeRepository = $storeRepository;
        $this->storeManager = $storeManager;
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->logger = $logger;
        $this->storeMessage = $storeMessage;
        $this->urlEncoder = $urlEncoder;
        $this->urlDecoder = $urlDecoder;

        $this->url = $context->getUrl();

        $this->countryCodeProvider = $countryCodeProvider;
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
            $countryCode = $this->countryCodeProvider->getCountryCode();
            $currentStore = $this->storeManager->getStore();
            $currentWebsiteId = $currentStore->getWebsiteId();
            $storeId = $this->config->getGeoIpMappingForCountryAndWebsite($countryCode, $currentWebsiteId);
            $targetUrl = $this->getRequest()->getParam('uenc');

            if ($storeId !== null) {
                // get redirect URL
                $redirectUrl = null;
                $targetStore = $this->storeRepository->getActiveStoreById($storeId);
                // only generate a redirect URL if current and new store are different
                if ($currentStore->getId() !== $targetStore->getId()) {
                    $this->url->setScope($targetStore->getId());
                    $targetStoreCode = $targetStore->getCode();
                    $currentStoreCode = $currentStore->getCode();

                    $queryParams = [
                        '___store' => $targetStoreCode,
                        '___from_store' => $currentStoreCode
                    ];
                    if ($targetUrl) {
                        $queryParams['uenc'] = $this->getTargetUrl($targetUrl, $targetStoreCode, $currentStoreCode);
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
                                $this->storeMessage->getMessageInStoreLocale($targetStore)
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
     * GetTargetUrl uenc params modificiations
     *
     * @param string $targetUrl
     * @param string $targetStoreCode
     * @param string $currentStoreCode
     * @return string
     */
    private function getTargetUrl($targetUrl, $targetStoreCode, $currentStoreCode): string
    {
        $decodedTargetUrl = $this->urlDecoder->decode($targetUrl);
        $path = parse_url($decodedTargetUrl, PHP_URL_PATH);

        /* Fix geoip redirection issue to the same page */
        $currentStore = $this->storeManager->getStore();
        $currentBaseUrl = $currentStore->getBaseUrl();
        $targetStore = $this->storeRepository->getActiveStoreByCode($targetStoreCode);
        $targetBaseUrl = $targetStore->getBaseUrl();
        $decodedTargetUrl = \str_ireplace($currentBaseUrl, $targetBaseUrl, $decodedTargetUrl);

        if (\preg_match("#^/$currentStoreCode(?:/|\?|$)#", $path)) {

            $decodedTargetUrl = \preg_replace(
                "#/$currentStoreCode#",
                "/$targetStoreCode",
                $decodedTargetUrl,
                1
            );
        }
        $encodedUrl = $this->urlEncoder->encode($decodedTargetUrl);
        return \explode('%', $encodedUrl)[0];
    }
}
