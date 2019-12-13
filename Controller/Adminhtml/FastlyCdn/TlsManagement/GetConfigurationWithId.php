<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement;

use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Model\ApiParametersResolver;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class GetConfigurationWithId
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement
 */
class GetConfigurationWithId extends Action
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
     * @var ApiParametersResolver
     */
    private $apiParametersResolver;

    /**
     * GetConfigurationWithId constructor.
     * @param Action\Context $context
     * @param Api $api
     * @param Http $request
     * @param ApiParametersResolver $apiParametersResolver
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Action\Context $context,
        Api $api,
        Http $request,
        ApiParametersResolver $apiParametersResolver,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->api = $api;
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->apiParametersResolver = $apiParametersResolver;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $id = $this->request->getParam('id');
        try {
            $response = $this->api->getSpecificTlsConfigurations($id);
            $response = $this->apiParametersResolver->combineDataAndIncludedConfigurations($response);
        } catch (LocalizedException $e) {
            return $result->setData([
                'status'    => false,
                'msg'   => $e->getMessage()
            ]);
        }

        if (!$response) {
            return $result->setData([
                'status'    => true,
                'flag'  => false,
                'msg'   => "change this shiat"
            ]);
        }

        return $result->setData([
            'status'    => true,
            'flag'  => true,
            'configuration' => $response
        ]);
    }
}
