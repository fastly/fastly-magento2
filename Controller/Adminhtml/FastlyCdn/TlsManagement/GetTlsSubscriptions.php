<?php

declare(strict_types=1);

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class GetTlsSubscriptions
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement
 */
class GetTlsSubscriptions extends Action
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
     * GetTlsSubscriptions constructor.
     * @param Action\Context $context
     * @param Api $api
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Action\Context $context,
        Api $api,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->api = $api;
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        try {
            $response = $this->api->getTlsSubscriptions();
        } catch (LocalizedException $e) {
            return $result->setData([
                'status'    => false,
                'msg'   => $e->getMessage()
            ]);
        }

        if (!$response) {
            return $result->setData([
                'status'    => true,
                'flag'      => false,
                'msg'   => 'Something went wrong, please try again'
            ]);
        }

        return $result->setData([
            'status'    => true,
            'flag'      => true,
            'data'      => $response->data,
            'included'  => $response->included
        ]);
    }
}
