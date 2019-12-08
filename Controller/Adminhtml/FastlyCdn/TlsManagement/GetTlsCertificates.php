<?php

declare(strict_types=1);

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class GetTlsCertificates
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement
 */
class GetTlsCertificates extends Action
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
     * GetTlsCertificates constructor.
     * @param Action\Context $context
     * @param JsonFactory $jsonFactory
     * @param Api $api
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        Api $api
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->api = $api;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        try {
            $response = $this->api->getTlsCertificates();
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
                'msg'   => 'You are not authorized to perform this action'
            ]);
        }

        return $result->setData([
            'status'    => true,
            'flag'    => true,
            'data'  => $response->data ?: []
        ]);
    }
}
