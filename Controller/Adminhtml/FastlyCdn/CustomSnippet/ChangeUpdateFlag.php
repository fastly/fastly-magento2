<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\CustomSnippet;

use Fastly\Cdn\Model\Config;
use Magento\Backend\App\Action;
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
        CoreConfig $coreConfig
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->coreConfig = $coreConfig;
    }

    public function execute()
    {
        $json = $this->jsonFactory->create();
        $this->coreConfig->saveConfig(Config::UPDATED_VCL_FLAG, 0);
        return $json->setData([
            'msg'       => 'Upload VCL to activate modified custom snippet'
        ]);
    }
}
