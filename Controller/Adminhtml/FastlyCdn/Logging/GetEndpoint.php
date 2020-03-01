<?php
/**
 * Fastly CDN for Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Fastly CDN for Magento End User License Agreement
 * that is bundled with this package in the file LICENSE_FASTLY_CDN.txt.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Fastly CDN to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Fastly
 * @package     Fastly_Cdn
 * @copyright   Copyright (c) 2016 Fastly, Inc. (http://www.fastly.com)
 * @license     BSD, see LICENSE_FASTLY_CDN.txt
 */
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Logging;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Api;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class GetEndpoint
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Logging
 */
class GetEndpoint extends Action
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
     * @var Api
     */
    private $api;

    /**
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Api $api
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Api $api
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->api = $api;
        parent::__construct($context);
    }

    /**
     * @return $this|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $endpoint = $this->api->getLogEndpoint(
                $this->getRequest()->getParam('active_version'),
                $this->getRequest()->getParam('type'),
                $this->getRequest()->getParam('name')
            );

            if (!$endpoint) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to check Endpoint details.'
                ]);
            }

            return $result->setData([
                'status'    => true,
                'endpoint' => $endpoint
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
