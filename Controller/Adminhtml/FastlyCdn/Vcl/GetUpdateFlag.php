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
     * GetUpdatedFlag constructor.
     * @param Action\Context $context
     * @param JsonFactory $jsonFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        $json = $this->jsonFactory->create();
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
