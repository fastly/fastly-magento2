<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Magento\Backend\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;

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

    public function __construct(
        Action\Context $context,
        Http $request,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $header = $this->request->getHeader('Fastly-Magento-VCL-Uploaded');
        if (!$header) {
            return $result->setData([
                'status' => false,
                'msg'    => 'The Vcl version is outdated!'
            ]);
        }
        return $result->setData([
            'status'    => true
        ]);
    }
}
