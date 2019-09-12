<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Fastly\Cdn\Model\Config;
use Magento\Backend\App\Action;
use Magento\Framework\App\Cache\TypeList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class GetUpdateFlag
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl
 */
class GetUpdateFlag extends Action
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    /**
     * @var TypeList
     */
    private $typeList;

    /**
     * GetUpdatedFlag constructor.
     * @param Action\Context $context
     * @param JsonFactory $jsonFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param TypeList $typeList
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        ScopeConfigInterface $scopeConfig,
        TypeList $typeList
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->jsonFactory = $jsonFactory;
        $this->typeList = $typeList;
    }

    public function execute()
    {
        $json = $this->jsonFactory->create();
        $this->typeList->cleanType('config');
        $flag = $this->scopeConfig->getValue(Config::UPDATED_VCL_FLAG);
        if (!$flag && $flag !== null) {
            return $json->setData([
                'flag'   => false,
                'msg'       => 'Upload VCL to activate modified custom snippet'
            ]);
        }

        return $json->setData([
            'flag'   => true
        ]);
    }
}
