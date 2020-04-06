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

use Fastly\Cdn\Helper\Vcl;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class UpdateEndpoint
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Logging
 */
class UpdateEndpoint extends Action
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
     * @var Vcl
     */
    private $vcl;
    /**
     * @var Config
     */
    private $config;

    /**
     * ConfigureBackend constructor
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Api $api
     * @param Vcl $vcl
     * @param Config $config
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Api $api,
        Vcl $vcl,
        Config $config
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->api = $api;
        $this->vcl = $vcl;
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $oldName = $this->getRequest()->getParam('old_name');
            $endpointType = $this->getRequest()->getParam('endpoint_type');
            if ($this->getRequest()->getParam('form') === 'false') {
                return $result->setData([
                    'endpointType' => $endpointType,
                    'status' => true,
                ]);
            }

            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive(
                $service->versions,
                $this->getRequest()->getParam('active_version')
            );
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $clone = $this->api->cloneVersion($currActiveVersion);

            $condition = $this->createCondition(
                $clone,
                $this->getRequest()->getParam('condition_name'),
                $this->getRequest()->getParam('apply_if'),
                $this->getRequest()->getParam('condition_priority'),
                $this->getRequest()->getParam('response_condition')
            );

            $params = array_merge(
                $this->getRequest()->getParam('log_endpoint'),
                ['response_condition' => $condition]
            );

            $endpoint = $this->api->updateLogEndpoint($clone->number, $endpointType, array_filter($params), $oldName);

            if (!$endpoint) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to update Endpoint: ' . $this->api->getLastErrorMessage()
                ]);
            }

            $this->api->validateServiceVersion($clone->number);

            if ($this->getRequest()->getParam('activate_flag') === 'true') {
                $this->api->activateVersion($clone->number);
            }

            $this->api->addComment(
                $clone->number,
                ['comment' => 'Magento Module update the "' . $params['name'] . '" Endpoint']
            );

            return $result->setData([
                'status'            => true,
                'active_version'    => $clone->number
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }

    /**
     * @param $clone
     * @param $conditionName
     * @param $applyIf
     * @param $conditionPriority
     * @param $selCondition
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createCondition($clone, $conditionName, $applyIf, $conditionPriority, $selCondition)
    {
        if ($conditionName == $selCondition && !empty($selCondition) &&
            !$this->api->getCondition($clone->number, $conditionName)) {
            $condition = [
                'name'      => $conditionName,
                'statement' => $applyIf,
                'type'      => 'RESPONSE',
                'priority'  => $conditionPriority
            ];
            $createCondition = $this->api->createCondition($clone->number, $condition);
            return $createCondition->name;
        }
        return $selCondition;
    }
}
