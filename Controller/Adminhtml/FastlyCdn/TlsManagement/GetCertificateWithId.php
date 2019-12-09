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

/**
 * Class GetCertificateWithId
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement
 */
class GetCertificateWithId extends Action
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
     * @var Http
     */
    private $request;

    /**
     * GetCertificateWithId constructor.
     * @param Action\Context $context
     * @param JsonFactory $jsonFactory
     * @param Http $request
     * @param Api $api
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        Http $request,
        Api $api
    ) {
        parent::__construct($context);
        $this->api = $api;
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $id = $this->request->getParam('id');
        try {
            $response = $this->api->getCertificateWithId($id);
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
                'msg'   => 'The certificate you requested was not found'
            ]);
        }

        return $result->setData([
            'status'    => true,
            'flag'  => true,
            'data'   => $response->data
        ]);
    }
}
