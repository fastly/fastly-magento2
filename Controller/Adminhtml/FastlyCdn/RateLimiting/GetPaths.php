<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\RateLimiting;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Fastly\Cdn\Model\Config;

/**
 * Class GetPaths
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\RateLimiting
 */
class GetPaths extends Action
{
    /**
     * @var Http
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $resultJson;

    /**
     * @var Config
     */
    private $config;

    /**
     * GetPaths constructor.
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\Result\Json|ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $paths = json_decode($this->config->getRateLimitPaths());

            if (!$paths) {
                return $result->setData([
                    'status'    => true,
                    'paths'     => ''
                ]);
            }

            return $result->setData([
                'status'    => true,
                'paths'     => $paths
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
