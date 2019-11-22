<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement\Domains;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class EnableTls
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement\Domains
 */
class EnableTls extends Action
{
    /**
     * @var Api
     */
    private $api;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    /**
     * @var Json
     */
    private $json;

    /**
     * EnableTls constructor.
     * @param Action\Context $context
     * @param Api $api
     * @param Json $json
     * @param Http $request
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Action\Context $context,
        Api $api,
        Json $json,
        Http $request,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->api = $api;
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->json = $json;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $configuration = $this->request->getParam('tls_configuration');
        $certificate = $this->request->getParam('tls_certificate');
        $domain = $this->request->getParam('tls_domain');

        if (!$configuration || !$certificate || !$domain) {
            return $result->setData([
                'status'    => false,
                'msg'   => 'Parameters are not provided'
            ]);
        }

        $activation['data'] = [
            'type' => 'tls_activation',
            'relationships'  => [
                'tls_certificate'   => [
                    'data'  => [
                        'type'  => 'tls_certificate',
                        'id'    => $certificate
                    ]
                ],
                'tls_configuration' => [
                    'data'  => [
                        'type'  => 'tls_configuration',
                        'id'    => $configuration
                    ]
                ],
                'tls_domain'    => [
                    'data'  => [
                        'type'  => 'tls_domain',
                        'id'    => $domain
                    ]
                ]
            ]
        ];

        try {
            $activation = $this->json->serialize($activation);
            $response = $this->api->enableTlsActivation($activation);
        } catch (LocalizedException $e) {
            return $result->setData([
                'status'    => true,
                'flag'  => false,
                'msg'   => $e->getMessage()
            ]);
        } catch (\InvalidArgumentException $e) {
            return $result->setData([
                'status'    => true,
                'flag'  => false,
                'msg'   => $e->getMessage()
            ]);
        }

        if (!$response) {
            return $result->setData([
                'status'    => true,
                'flag'  => false,
                'msg'   => 'Something went wrong, please try again'
            ]);
        }

        return $result->setData([
            'status'    => true,
            'flag'  => true,
            'msg'   => __('Successfully enabled TLS for ' . $domain)
        ]);
    }
}
