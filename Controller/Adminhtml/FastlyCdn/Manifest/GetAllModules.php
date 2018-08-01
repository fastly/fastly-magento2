<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Manifest;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Fastly\Cdn\Model\Modly\Manifest;

/**
 * Class GetCustomSnippets
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl
 */
class GetAllModules extends Action
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
     * @var Manifest
     */
    private $manifest;

    /**
     * GetAllModules constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Manifest $manifest
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Manifest $manifest
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->manifest = $manifest;
        parent::__construct($context);
    }

    /**
     * Get a list of all modules
     *
     * @return $this|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $modules = $this->manifest->getAllModlyManifests();

            if (!$modules) {
                return $result->setData([
                    'status'    => false,
                    'modules'   => '',
                    'msg'       => 'Use the Refresh button to get the latest modules.'
                ]);
            }

            return $result->setData([
                'status'    => true,
                'modules'  => $modules
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
