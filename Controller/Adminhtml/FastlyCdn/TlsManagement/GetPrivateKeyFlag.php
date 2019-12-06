<?php

declare(strict_types=1);

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement;

use Fastly\Cdn\Model\Config;
use Magento\Backend\App\Action;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class GetPrivateKeyFlag
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement
 */
class GetPrivateKeyFlag extends Action
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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * GetPrivateKeyFlag constructor.
     * @param Action\Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Action\Context $context,
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->jsonFactory = $jsonFactory;
        $this->serializer = $serializer;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $isPrivateKeyCreated = $this->scopeConfig->getValue(Config::IS_PRIVATE_KEY_UPDATED) === 'true';

        if (!$isPrivateKeyCreated) {
            return $result->setData([
                'flag'  => $isPrivateKeyCreated
            ]);
        }

        $privateKeyJson = $this->scopeConfig->getValue(Config::LAST_INSERTED_PRIVATE_KEY);
        $privateKey = $this->serializer->unserialize($privateKeyJson);
        return $result->setData([
            'flag'  => $isPrivateKeyCreated,
            'privateKey'    => $privateKey
        ]);
    }
}
