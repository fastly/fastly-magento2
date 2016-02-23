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
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\StoreResolver;

class GetAction extends \Magento\Framework\App\Action\Action
{
    /**
     * Request parameter for the country code.
     */
    const REQUEST_PARAM_COUNTRY = 'country_code';

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
     * @param Context $context
     * @param Config $config
     * @param StoreRepositoryInterface $storeRepository
     * @param StoreManagerInterface $storeManager
     * @param LayoutFactory $resultLayoutFactory
     */
    public function __construct(
        Context $context,
        Config $config,
        StoreRepositoryInterface $storeRepository,
        StoreManagerInterface $storeManager,
        LayoutFactory $resultLayoutFactory
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->context = $context;
        $this->storeRepository = $storeRepository;
        $this->storeManager = $storeManager;
        $this->resultLayoutFactory = $resultLayoutFactory;
    }

    /**
     * Return country action.
     *
     * @return LayoutFactory
     */
    public function execute()
    {
        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->addDefaultHandle();

        $countryCode = $this->getRequest()->getParam(self::REQUEST_PARAM_COUNTRY);
        if (preg_match('/^[A-Z]{2}$/', $countryCode) === 1) {
            switch ($this->config->getGeoIpAction()) {
                case Config::GEOIP_ACTION_DIALOG:
                    // show a dialog from CMS block
                    if ($cmsBlockId = $this->config->getGeoIpDialogMappingForCountry($countryCode)) {
                        $cmsBlock = $this->_view->getLayout()->createBlock('cms/block')->setBlockId($cmsBlockId);
                        $output = $this->_view->getLayout()->createBlock('Magento\Framework\View\Element\Template')
                            ->setTemplate('Fastly_CDN::geoip/dialog.phtml')
                            ->setChild('esiPopup', $cmsBlock);
                    }
                    break;
                case Config::GEOIP_ACTION_REDIRECT:
                    if ($storeId = $this->config->getGeoIpRedirectMappingForCountry($countryCode)) {
                        $redirectUrl = null;
                        try {
                            $newStore = $this->storeRepository->getActiveStoreById($storeId);
                            $currentStore = $this->storeManager->getStore();

                            if ($currentStore->getId() != $newStore->getId()) {
                                $redirectUrl = $this->context->getUrl()->getUrl(
                                    'stores/store/switch',
                                    [StoreResolver::PARAM_NAME => $newStore->getCode()]
                                );
                            }
                        } catch (Exception $e) {
                            // do nothing
                        }
                        $resultLayout->getLayout()->getUpdate()->load(['geoip_getaction']);
                        $resultLayout->getLayout()->getBlock('geoip_getaction')->setRedirectUrl($redirectUrl);
                    }
                    break;
            }
        }

        return $resultLayout;
    }
}
