<?php

declare(strict_types=1);

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement;

use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Model\Config;
use Magento\Backend\App\Action;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class GetTlsSubscriptions
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement
 */
class GetTlsSubscriptions extends Action
{
    /**
     * @var Api
     */
    private $api;
    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * GetTlsSubscriptions constructor.
     * @param Action\Context $context
     * @param Api $api
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Action\Context $context,
        Api $api,
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->api = $api;
        $this->jsonFactory = $jsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        try {
            $response = $this->api->getTlsSubscriptions();
        } catch (LocalizedException $e) {
            return $result->setData([
                'status'    => false,
                'msg'   => $e->getMessage()
            ]);
        }

        if (!$response) {
            return $result->setData([
                'status'    => true,
                'flag'      => false,
                'msg'   => 'You are not authorized to perform this action'
            ]);
        }

        $isPrivateKeyCreated = $this->scopeConfig->getValue(Config::IS_PRIVATE_KEY_UPDATED) === 'true';

        if (!$isPrivateKeyCreated) {
            return $result->setData([
                'status'    => true,
                'flag'      => true,
                'isPrivateKeyCreatedWithoutCertificate'   => false,
                'data'      => $response->data
            ]);
        }

        $privateKeyJson = $this->scopeConfig->getValue(Config::LAST_INSERTED_PRIVATE_KEY);
        $privateKey = $this->serializer->unserialize($privateKeyJson);
        return $result->setData([
            'status'    => true,
            'flag'      => true,
            'isPrivateKeyCreatedWithoutCertificate'    => true,
            'privateKey'    => $privateKey,
            'data'      => $response->data
        ]);
    }
}
