<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Fastly\Cdn\Model\Config as FastlyConfig;
use Magento\Backend\App\Action;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeList;

class DismissWarning extends Action
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var Data
     */
    private $jsonHelper;
    /**
     * @var Http
     */
    private $request;
    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var CacheTypeList
     */
    private $typeList;

    /**
     * DismissWarning constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param JsonFactory $jsonFactory
     * @param Data $jsonHelper
     * @param Http $request
     * @param Config $config
     * @param CacheTypeList $typeList
     * @param Action\Context $context
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        JsonFactory $jsonFactory,
        Data $jsonHelper,
        Http $request,
        Config $config,
        CacheTypeList $typeList,
        Action\Context $context
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->jsonHelper = $jsonHelper;
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
        $this->typeList = $typeList;
    }

    public function execute()
    {
        $activeVersion = $this->request->getParam('active_version');
        $result = $this->jsonFactory->create();
        if (!$activeVersion) {
            return $result->setData([
                'status' => false,
                'msg'   => 'Something went wrong, please try again later.'
            ]);
        }
        $coreConfigData = $this->scopeConfig->getValue(FastlyConfig::VERSIONS_WITH_DISMISSED_WARNING);
        $coreConfigData = $coreConfigData ? $this->jsonHelper->jsonDecode($coreConfigData) : [];
        if (!in_array($activeVersion, $coreConfigData)) {
            $coreConfigData[] = $activeVersion;
            $coreConfigData = $this->jsonHelper->jsonEncode($coreConfigData);
            $this->config->saveConfig(FastlyConfig::VERSIONS_WITH_DISMISSED_WARNING, $coreConfigData);
            $this->typeList->cleanType('config');
            return $result->setData([
                'status' => true,
                'msg'   => 'Successfully dismissed warning'
            ]);
        }
        return $result->setData([
            'status' => false,
            'msg'   => 'You already dismissed warning for this version.'
        ]);
    }
}
