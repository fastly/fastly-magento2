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
namespace Fastly\Cdn\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface as LocaleResolverInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class StoreMessage
 * @package Fastly\Cdn\Helper
 */
class StoreMessage extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /***
     * @var LocaleResolverInterface
     */
    private $localeResolver;

    /**
     * StoreMessage constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param LocaleResolverInterface $localeResolver
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        LocaleResolverInterface $localeResolver
    ) {
        parent::__construct($context);
        $this->storeManager         = $storeManager;
        $this->localeResolver       = $localeResolver;
    }

    /**
     * @param StoreInterface $emulatedStore
     * @return string
     * @throws NoSuchEntityException
     */
    public function getMessageInStoreLocale(StoreInterface $emulatedStore): string
    {
        $currentStore = $this->storeManager->getStore();

        // emulate locale and store of new store to fetch message translation
        $this->localeResolver->emulate($emulatedStore->getId());
        $this->storeManager->setCurrentStore($emulatedStore->getId());

        $message = (string)__(
            'You are in the wrong store. Click OK to visit the %1 store.',
            [$emulatedStore->getName()]
        );

        // revert locale and store emulation
        $this->localeResolver->revert();
        $this->storeManager->setCurrentStore($currentStore->getId());

        return $message;
    }
}
