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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Backend;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Api;
use Magento\Framework\Controller\ResultInterface;
use stdClass;

/**
 * Class GetBackends
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Backend
 */
class GetBackends extends Action
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
     * GetBackends constructor.
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
     * Get all backends for active version
     *
     * @return $this|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $backends = $this->api->getBackends($activeVersion);

            if (!$backends) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to check Backend details.'
                ]);
            }

            return $result->setData([
                'status'    => true,
                'data_centers' => $this->groupDataCenters($this->api->getDataCenters()),
                'backends'  => $backends
            ]);
        } catch (Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }

    /**
     * @param $dataCenters
     * @return array|false
     */
    public function groupDataCenters($dataCenters)
    {
        if (!$dataCenters)
            return false;

        $data = [];
        foreach ($dataCenters as $dataCenter) {
            if (!isset($dataCenter->group) || !isset($dataCenter->name)
                || !isset($dataCenter->code) || !isset($dataCenter->shield))
                continue;

            $data[$dataCenter->group][] = [
                'value'    => $dataCenter->shield,
                'label'     => $dataCenter->name . ' (' . $dataCenter->code . ')'
            ];
        }

        return $data;
    }
}
