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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Acl;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Acl;

/**
 * Class GetAcl
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Acl
 */
class GetAcl extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJson;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Acl
     */
    private $acl;

    /**
     * GetAcl constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Api $api
     * @param Acl $acl
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Api $api,
        Acl $acl
    ) {
        $this->resultJson = $resultJsonFactory;
        $this->api = $api;
        $this->acl = $acl;
        parent::__construct($context);
    }

    /**
     * Get ACL
     *
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();

        try {
            $service = $this->api->checkServiceDetails();
            if (!$service) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to check Service details.'
                ]);
            }

            return $result->$this->acl->determineVersions($service->versions);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
