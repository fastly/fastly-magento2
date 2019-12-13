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
 * Class DisableTlsActivation
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement
 */
class DisableTlsActivation extends Action
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
     * DisableTlsActivation constructor.
     * @param Action\Context $context
     * @param Api $api
     * @param Http $request
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Action\Context $context,
        Api $api,
        Http $request,
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
        $activation = $this->request->getParam('activation');
        try {
            $response = $this->api->disableTlsActivation($activation);
        } catch (LocalizedException $e) {
            return $result->setData([
                'status'    => false,
                'msg'   => $e->getMessage()
            ]);
        }

        if ($response !== null) {
            return $result->setData([
                'status'    => false,
                'flag'  => true,
                'msg'   => 'Something went wrong, please try again.'
            ]);
        }

        return $result->setData([
            'status'    => true,
            'flag'  => true,
            'msg'   => 'Successfully disabled TLS.'
        ]);
    }
}
