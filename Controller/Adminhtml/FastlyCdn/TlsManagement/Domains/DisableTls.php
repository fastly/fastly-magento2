<?php

declare(strict_types=1);

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement\Domains;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class DisableTls
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement\Domains
 */
class DisableTls extends Action
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
     * DisableTls constructor.
     * @param Action\Context $context
     * @param Http $request
     * @param Api $api
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Action\Context $context,
        Http $request,
        Api $api,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->api = $api;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();

        try {
            $response = $this->api->disableTlsActivation($this->request->getParam('id'));
        } catch (LocalizedException $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }

        if ($response !== null && !$response) {
            return $result->setData([
                'status' => true,
                'flag'  => false,
                'msg'   => 'Something went wrong, please try again later.'
            ]);
        }

        return $result->setData([
            'status' => true,
            'flag'  => true,
            'msg'   => 'Successfully disabled TLS! Enabling TLS may add additional charges to your account.'
        ]);
    }
}
