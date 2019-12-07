<?php

declare(strict_types=1);

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement;

use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Model\Config;
use Magento\Backend\App\Action;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Class CreateTlsCertificate
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement
 */
class CreateTlsCertificate extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var JsonSerializer
     */
    private $json;
    /**
     * @var TypeListInterface
     */
    private $typeList;
    /**
     * @var WriterInterface
     */
    private $writer;

    /**
     * CreateTlsCertificate constructor.
     * @param Action\Context $context
     * @param JsonFactory $jsonFactory
     * @param JsonSerializer $json
     * @param Http $request
     * @param WriterInterface $writer
     * @param TypeListInterface $typeList
     * @param Api $api
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        JsonSerializer $json,
        Http $request,
        WriterInterface $writer,
        TypeListInterface $typeList,
        Api $api
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->api = $api;
        $this->request = $request;
        $this->json = $json;
        $this->typeList = $typeList;
        $this->writer = $writer;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $data['data'] = [
            'type'  => 'tls_certificate',
            'attributes'    => [
                'cert_blob' => $this->request->getParam('certificate'),
                'name'  => $this->request->getParam('name')
            ]
        ];

        try {
            $data = $this->json->serialize($data);
            $response = $this->api->createTlsCertificate($data);
        } catch (LocalizedException $e) {
            return $result->setData([
                'status'    => false,
                'msg'   => __($e->getMessage())
            ]);
        }

        if (!$response) {
            return $result->setData([
                'status'    => true,
                'flag'  => false,
                'msg'   => "dawdawd" //todo: change this
            ]);
        }

        return $result->setData([
            'status'    => true,
            'flag'  => true,
            'data'   => $response->data,
            'msg'   => 'You successfully uploaded this certificate to Fastly'
        ]);
    }
}
