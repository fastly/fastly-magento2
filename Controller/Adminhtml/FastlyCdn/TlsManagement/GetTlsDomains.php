<?php

declare(strict_types=1);

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class GetTlsDomains
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement
 */
class GetTlsDomains extends Action
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
     * GetTlsDomains constructor.
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

    public function execute()
    {
        $json = $this->jsonFactory->create();
        try {
            $result = $this->api->getTlsDomains();
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
                'msg'    => 'You are not authorized to perform this action. '
            ]);
        }

        return $json->setData([
            'status' => true,
            'flag'  => true,
            'domains'    => $result->data ?: false,
            'meta'  => $result->meta
        ]);
    }
}
