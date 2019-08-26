<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Notification;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Json\Helper\Data;

class Comparison extends Action
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
     * @var Data
     */
    private $jsonHelper;

    /**
     * @var Notification
     */
    private $notification;

    /**
     * Comparison constructor.
     *
     * @param Action\Context $context
     * @param Http           $request
     * @param JsonFactory    $jsonFactory
     * @param Data           $jsonHelper
     * @param Notification   $notification
     */
    public function __construct(
        Action\Context $context,
        Http $request,
        JsonFactory $jsonFactory,
        Data $jsonHelper,
        Notification $notification
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->jsonHelper = $jsonHelper;
        $this->notification = $notification;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        $vclVersion = $this->notification->getLastVersion();
        $localVersion = $this->request->getHeader(Config::REQUEST_HEADER);
        if ($vclVersion != $localVersion) {
            return $result->setData(
                [
                'status' => false,
                'msg'   => 'Plugin VCL version is outdated! Please re-Upload.'
                ]
            );
        }

        return $result->setData(
            [
            'status'    => true
            ]
        );
    }
}
