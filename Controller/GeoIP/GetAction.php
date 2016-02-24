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
 * @package     Fastly_CDN
 * @copyright   Copyright (c) 2016 Fastly, Inc. (http://www.fastly.com)
 * @license     BSD, see LICENSE_FASTLY_CDN.txt
 */
namespace Fastly\CDN\Controller\GeoIP;

use Fastly\CDN\Model\Config;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Locale\ResolverInterface as LocaleResolverInterface;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\StoreResolver;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class GetAction
 *
 * @package Fastly\CDN\Controller\GeoIP
 */
class GetAction extends \Magento\Framework\App\Action\Action
{
    /**
     * Request parameter for the country code.
     */
    const HEADER_PARAM_COUNTRY = 'X-GeoIP-Country-Code';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var LayoutFactory
     */
    protected $resultLayoutFactory;

    /**
     * @var LocaleResolverInterface
     */
    protected $localeResolver;

    /**
     * @param Context $context
     * @param Config $config
     * @param StoreRepositoryInterface $storeRepository
     * @param StoreManagerInterface $storeManager
     * @param LayoutFactory $resultLayoutFactory
     * @param LocaleResolverInterface $localeResolver
     */
    public function __construct(
        Context $context,
        Config $config,
        StoreRepositoryInterface $storeRepository,
        StoreManagerInterface $storeManager,
        LayoutFactory $resultLayoutFactory,
        LocaleResolverInterface $localeResolver
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->context = $context;
        $this->storeRepository = $storeRepository;
        $this->storeManager = $storeManager;
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->localeResolver = $localeResolver;
    }

    /**
     * Return country action.
     *
     * @return LayoutFactory
     */
    public function execute()
    {
        $resultLayout = null;

        try {
            // get target store from country code
            $countryCode = $this->getRequest()->getHeader(self::HEADER_PARAM_COUNTRY);
            $storeId = $this->config->getGeoIpMappingForCountry($countryCode);

            if (!is_null($storeId)) {
                // get redirect URL
                $redirectUrl = null;
                $targetStore = $this->storeRepository->getActiveStoreById($storeId);
                $currentStore = $this->storeManager->getStore();
                // only generate a redirect URL if current and new store are different
                if ($currentStore->getId() != $targetStore->getId()) {
                    $redirectUrl = $this->context->getUrl()->getUrl(
                        'stores/store/switch',
                        [StoreResolver::PARAM_NAME => $targetStore->getCode()]
                    );
                }

                // prepare layout only if a store redirect should be performed
                if ($redirectUrl) {
                    $resultLayout = $this->resultLayoutFactory->create();
                    $resultLayout->addDefaultHandle();

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
        } catch (Exception $e) {
            // do nothing
        }

        return $resultLayout;
    }

    /**
     * Gets the dialog message in the locale of the target store.
     *
     * @param StoreInterface $emulatedStore
     * @return string
     */
    protected function getMessageInStoreLocale(StoreInterface $emulatedStore)
    {
        /**
         * @todo the code doesn't seem to do what it should. refactor it to make it right.
         */

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
