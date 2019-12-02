<?php

declare(strict_types=1);

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Class SecureAnotherDomain
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement
 */
class SecureAnotherDomain extends Action
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
     * @var Api
     */
    private $api;
    /**
     * @var JsonSerializer
     */
    private $json;

    /**
     * SecureAnotherDomain constructor.
     * @param Action\Context $context
     * @param JsonFactory $jsonFactory
     * @param Http $request
     * @param JsonSerializer $json
     * @param Api $api
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        Http $request,
        JsonSerializer $json,
        Api $api
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->api = $api;
        $this->json = $json;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        $domain = $this->request->getParam('tls_domains');
        $config = $this->request->getParam('tls_configuration');
        $subscription['data'] = [
            'type' => 'tls_subscription',
            'attributes'    => [
                'certificate_authority' => 'lets-encrypt'
            ],
            'relationships'  => [
                'tls_domains'   => [
                    'data'  => [
                        [
                            'type'  => 'tls_domain',
                            'id'    => $domain
                        ]
                    ]
                ],
                'tls_configuration'    => [
                    'data'  => [
                        'type'  => 'tls_configuration',
                        'id'    => $config
                    ]
                ]
            ]
        ];

        try {
            $subscription = $this->json->serialize($subscription);
            $response = $this->api->secureAnotherDomain($subscription);
        } catch (LocalizedException $e) {
            return $result->setData([
              'status'  => false,
              'msg' => $e->getMessage()
            ]);
        }

        if (!$response) {
            return $result->setData([
                'status'    => true,
                'msg'   => __('A technical problem with the server created an error.'
                           . 'Try again to continue what you were doing.'
                           . 'If the problem persists, try again later.'),
                'flag'  => false
            ]);
        }

        return $result->setData([
            'status'    => true,
            'flag'  => true,
            'domain' => $response->data->relationships->tls_domains->data[0]->id,
            'state' => __('Fastly is verifying domain ownership.'),
            'msg' => __("Successfully! What's next? Fastly is verifying domain ownership"
                        . 'and Fastly is waiting for the Certificate Authority’s response.')
        ]);
    }
}
