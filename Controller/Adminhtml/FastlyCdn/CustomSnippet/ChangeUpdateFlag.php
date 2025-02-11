<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\CustomSnippet;

use Fastly\Cdn\Model\Config;
use Magento\Backend\App\Action;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Config\Model\ResourceModel\Config as CoreConfig;

/**
 * Class ChangeUpdateFlag
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\CustomSnippet
 */
class ChangeUpdateFlag extends Action
{
    const ADMIN_RESOURCE = 'Magento_Config::config';

    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    /**
     * @var Http
     */
    private $request;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var CoreConfig
     */
    private $coreConfig;
    /**
     * @var CacheTypeList
     */
    private $typeList;

    /**
     * ChangeUpdateFlag constructor.
     * @param Action\Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Http $request
     * @param JsonFactory $jsonFactory
     * @param CoreConfig $coreConfig
     */
    public function __construct(
        Action\Context $context,
        ScopeConfigInterface $scopeConfig,
        Http $request,
        JsonFactory $jsonFactory,
        CoreConfig $coreConfig,
        TypeListInterface $typeList
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->coreConfig = $coreConfig;
        $this->typeList = $typeList;
    }

    public function execute()
    {
        $json = $this->jsonFactory->create();
        $this->coreConfig->saveConfig(Config::UPDATED_VCL_FLAG, 0, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
        $this->typeList->cleanType('config');
        return $json->setData([
            'msg'       => 'Upload VCL to activate modified custom snippet'
        ]);
    }
}
