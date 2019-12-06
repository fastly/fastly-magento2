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
 * Class CreateTlsPrivateKey
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement
 */
class CreateTlsPrivateKey extends Action
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
     * @param TypeListInterface $typeList
     * @param WriterInterface $writer
     * @param Http $request
     * @param Api $api
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        JsonSerializer $json,
        TypeListInterface $typeList,
        WriterInterface $writer,
        Http $request,
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
        $key = $this->request->getParam('private_key');
        $name = $this->request->getParam('name');

        $data['data'] = [
            'type'  => 'tls_private_key',
            'attributes'    => [
                'key' => $key,
                'name'  => $name
            ]
        ];

        try {
            $data = $this->json->serialize($data);
            $response = $this->api->createTlsPrivateKey($data);
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

        $this->typeList->cleanType('config');
        $privateKeyJson = $this->json->serialize(['name' => $name, 'key' => $response->data->id]);
        $this->writer->save(Config::LAST_INSERTED_PRIVATE_KEY, $privateKeyJson);
        $this->writer->save(Config::IS_PRIVATE_KEY_UPDATED, 'true');
        return $result->setData([
            'status'    => true,
            'flag'  => true,
            'msg'   => 'You successfully uploaded this private key to Fastly. Now, upload the matching certificate.',
            'data'   => $response->data
        ]);
    }
}
