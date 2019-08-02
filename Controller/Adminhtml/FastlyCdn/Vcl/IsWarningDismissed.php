<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Fastly\Cdn\Model\Config as FastlyConfig;
use Magento\Backend\App\Action;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeList;

/**
 * Class IsWarningDismissed
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl
 */
class IsWarningDismissed extends Action
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var Http
     */
    private $request;
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Data
     */
    private $jsonHelper;
    /**
     * @var CacheTypeList
     */
    private $typeList;

    /**
     * IsWarningDismissed constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Http $request
     * @param Action\Context $context
     * @param JsonFactory $jsonFactory
     * @param Data $jsonHelper
     * @param CacheTypeList $typeList
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Http $request,
        Action\Context $context,
        JsonFactory $jsonFactory,
        Data $jsonHelper,
        CacheTypeList $typeList
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->jsonHelper = $jsonHelper;
        $this->typeList = $typeList;
    }

    public function execute()
    {
        $activeVersion = $this->request->getParam('active_version');
        $result = $this->jsonFactory->create();
        if (!$activeVersion) {
            return $result->setData([
                'status' => false,
                'msg' => 'Something went wrong, please try again'
            ]);
        }

        $coreConfigData = $this->scopeConfig->getValue(FastlyConfig::VERSIONS_WITH_DISMISSED_WARNING);
        if (!$coreConfigData) {
            return $result->setData([
                'status' => true,
                'dismissed' => false
            ]);
        }

        $coreConfigData = $this->jsonHelper->jsonDecode($coreConfigData);
        if (!in_array($activeVersion, $coreConfigData)) {
            return $result->setData([
                'status' => true,
                'dismissed' => false
            ]);
        }

        return $result->setData([
            'status' => true,
            'dismissed' => true
        ]);
    }
}
