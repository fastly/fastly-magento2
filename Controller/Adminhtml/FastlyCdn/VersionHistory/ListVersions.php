<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\VersionHistory;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class ListVersions
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\VersionHistory
 */
class ListVersions extends Action
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
     * ListVersions constructor.
     * @param JsonFactory $jsonFactory
     * @param Api $api
     * @param Context $context
     */
    public function __construct(
        JsonFactory $jsonFactory,
        Api $api,
        Context $context
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->api = $api;
    }

    /**
     * Gets all possible versions from Api
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        try {
            $service = $this->api->getServiceDetails();
            if (!$service) {
                return $result->setData([
                    'status' => false,
                    'msg' => 'Something went wrong, please try again later'
                ]);
            }
            return $result->setData([
                'status' => true,
                'versions' => $service->versions,
                'active_version' => $service->active_version,
                'number_of_pages' => (int)ceil(count($service->versions) / 10),
                'number_of_versions' => (int)count($service->versions)
            ]);
        } catch (LocalizedException $exception) {
            return $result->setData([
                'status' => false,
                'msg' => $exception->getMessage()
            ]);
        }
    }
}
