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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Domains;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;

/**
 * Class PushDomains
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Domains
 */
class PushDomains extends Action
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
     * @var Api
     */
    private $api;
    /**
     * @var Vcl
     */
    private $vcl;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * PushDomains constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        StoreManagerInterface $storeManager,
        Config $config,
        Api $api,
        Vcl $vcl
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();

        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $currentDomains = $this->getRequest()->getParam('current_domains');
            $newDomains = $this->getRequest()->getParam('domains');
            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $storeBaseUrl = $this->storeManager->getStore()->getBaseUrl();

            if (!$currentDomains && !$newDomains) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'At least one domain must exist.'
                ]);
            }

            $currentDomainData = $this->processCurrent($currentDomains);
            $newDomainData = $this->processNew($newDomains);

            $domainsToCreate = array_diff_assoc($newDomainData, $currentDomainData);
            $domainsToDelete = array_diff_assoc($currentDomainData, $newDomainData);

            foreach ($domainsToDelete as $name => $comment) {
                if (strpos($storeBaseUrl, $name) !== false) {
                    return $result->setData([
                        'status'    => false,
                        'msg'       => 'Cannot remove your current domain.'
                    ]);
                }
            }

            $clone = $this->api->cloneVersion($currActiveVersion);

            foreach ($domainsToCreate as $name => $comment) {
                $data = [
                    'name'      => $name,
                    'comment'   => $comment
                ];
                $createDomain = $this->api->createDomain($clone->number, $data);

                if (!$createDomain) {
                    return $result->setData([
                        'status'    => false,
                        'msg'       => 'Failed to activate changes. Some domains may already be registered.'
                    ]);
                }
            }

            foreach ($domainsToDelete as $name => $comment) {
                $deleteDomain = $this->api->deleteDomain($clone->number, $name);

                if (!$deleteDomain) {
                    return $result->setData([
                        'status'    => false,
                        'msg'       => 'Failed to activate changes.'
                    ]);
                }
            }

            $this->api->validateServiceVersion($clone->number);

            $this->api->activateVersion($clone->number);

            $comment = ['comment' => 'Magento Module pushed domains'];
            $this->api->addComment($clone->number, $comment);

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
     * @param $currentDomains
     * @return array
     */
    private function processCurrent($currentDomains)
    {
        if (!$currentDomains) {
            $currentDomains = [];
        }

        $currentDomainData = [];
        foreach ($currentDomains as $current) {
            $currentName = $current['name'];
            $currentComment = $current['comment'];
            $currentDomainData[$currentName] = $currentComment;
        }
        return $currentDomainData;
    }

    /**
     * @param $newDomains
     * @return array
     * @throws LocalizedException
     */
    private function processNew($newDomains)
    {
        if (!$newDomains) {
            $newDomains = [];
        }

        $newDomainData = [];
        foreach ($newDomains as $new) {
            $newName = $new['name'];
            $newComment = $new['comment'];
            $newDomainData[$newName] = $newComment;
            if (!preg_match('/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,6}$/', $newName)) {
                throw new LocalizedException(__('Invalid domain name "'.$newName.'"'));
            }
        }
        return $newDomainData;
    }
}
