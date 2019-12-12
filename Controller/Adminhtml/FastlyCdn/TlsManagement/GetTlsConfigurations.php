<?php

declare(strict_types=1);

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement;

use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Model\DomainParametersResolver;
use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class GetTlsConfigurations
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement
 */
class GetTlsConfigurations extends Action
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
     * CheckTlsPermission constructor.
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
    public function execute()
    {
        $json = $this->jsonFactory->create();
        try {
            $result = $this->api->getTlsConfigurations();
        } catch (LocalizedException $e) {
            return $json->setData([
               'status' => false,
               'msg'    => $e->getMessage()
            ]);
        }

        if (!$result) {
            return $json->setData([
                'status' => true,
                'flag'   => false,
                'msg'    => 'Adding a domain to a shared certificate requires a valid payment method on your account. '
                            . 'Please upgrade to a paid account '
                             . 'in order to use this service or reach out to support@fastly.com.'
            ]);
        }

        return $json->setData([
            'status' => true,
            'flag'  => true,
            'configurations'    => $result->data,
            'meta'  => $result->meta
        ]);
    }
}
