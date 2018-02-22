<?php

namespace Fastly\Cdn\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    const FASTLY_MODULE_NAME = 'Fastly_Cdn';

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Data constructor.
     * @param Context $context
     * @param ModuleListInterface $moduleList
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        StoreManagerInterface $storeManager
    ) {
        $this->moduleList = $moduleList;
        $this->storeManager = $storeManager;

        parent::__construct($context);
    }

    /**
     * Return Fastly module version
     *
     * @return string
     */
    public function getModuleVersion()
    {
        return $this->moduleList->getOne(self::FASTLY_MODULE_NAME)['setup_version'];
    }

    /**
     * Return Store name
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->storeManager->getStore()->getName();
    }

    /**
     * Return Store URL
     *
     * @return mixed
     */
    public function getStoreUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }
}
