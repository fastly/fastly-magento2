<?php

declare(strict_types=1);

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class EnableTlsActivation
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement
 */
class EnableTlsActivation extends Action
{
    /**
     * @var Http
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * EnableTlsActivation constructor.
     * @param Action\Context $context
     * @param SerializerInterface $serializer
     * @param Api $api
     * @param JsonFactory $jsonFactory
     * @param Http $request
     */
    public function __construct(
        Action\Context $context,
        SerializerInterface $serializer,
        Api $api,
        JsonFactory $jsonFactory,
        Http $request
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->api = $api;
        $this->serializer = $serializer;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $activation['data'] = [
            'type'  => 'tls_activation',
            'relationships' => [
                'tls_certificate'   => [
                    'data'  => [
                        'type'  => 'tls_certificate',
                        'id'    => $this->request->getParam('certificate')
                    ]
                ],
                'tls_configuration' => [
                    'data'  => [
                        'type'  => 'tls_configuration',
                        'id'    => $this->request->getParam('configuration')
                    ]
                ],
                'tls_domain'    => [
                    'data'  => [
                        'type'  => 'tls_domain',
                        'id'    => $this->request->getParam('domain')
                    ]
                ]
            ]
        ];

        $activation = $this->serializer->serialize($activation);
        try {
            $response = $this->api->enableTlsActivation($activation);
        } catch (LocalizedException $e) {
            return $result->setData([
                'status'    => false,
                'msg'   => $e->getMessage()
            ]);
        }

        if ($response !== null) {
            return $result->setData([
                'status'    => true,
                'flag'  => true,
                'msg'   => 'Successfully enabled TLS.'
            ]);
        }

        return $result->setData([
            'status'    => true,
            'flag'  => false,
            'msg'   => 'Something went wrong, please try again.'
        ]);
    }
}
