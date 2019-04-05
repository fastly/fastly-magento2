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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Maintenance;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class UpdateSuIps
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Maintenance
 */
class UpdateSuIps extends Action
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
     * @var Filesystem
     */
    private $filesystem;

    /**
     * UpdateSuIps constructor.
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl,
        Filesystem $filesystem
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
        $this->filesystem = $filesystem;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $service = $this->api->checkServiceDetails();
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);

            $aclName = Config::MAINT_ACL_NAME;
            $acl = $this->api->getSingleAcl($currActiveVersion, $aclName);

            if (!$acl) {
                throw new LocalizedException(__(
                    'The required ACL container does not exist. Please re-upload VCL.'
                ));
            } else {
                $ipList = $this->readMaintenanceIp();
                if (!$ipList) {
                    return $result->setData([
                        'status'    => false,
                        'msg'       => 'Please make sure that the maintenance.ip file contains at least one IP address.'
                    ]);
                }
                $aclId = $acl->id;
                $aclItems = $this->api->aclItemsList($aclId);
                $comment = 'Added for Maintenance Mode';

                $this->deleteIps($aclItems, $aclId);

                foreach ($ipList as $ip) {
                    if ($ip[0] == '!') {
                        $ip = ltrim($ip, '!');
                    }

                    // Handle subnet
                    $ipParts = explode('/', $ip);
                    $subnet = false;
                    if (!empty($ipParts[1])) {
                        if (is_numeric($ipParts[1]) && (int)$ipParts[1] < 129) {
                            $subnet = $ipParts[1];
                        } else {
                            continue;
                        }
                    }

                    if (!filter_var($ipParts[0], FILTER_VALIDATE_IP)) {
                        throw new LocalizedException(__(
                            'IP validation failed, please make sure that the provided IP values are comma-separated 
                            and valid'
                        ));
                    }

                    $this->api->upsertAclItem($aclId, $ipParts[0], 0, $comment, $subnet);
                }
            }

            $this->sendWebHook();

            return $result->setData(['status' => true]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function readMaintenanceIp()
    {
        $flagDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);

        if ($flagDir->isExist('.maintenance.ip')) {
            $temp = $flagDir->readFile('.maintenance.ip');
            $tempList = explode(',', trim($temp));
            foreach ($tempList as $key => $value) {
                if (empty($value) || !trim($value)) {
                    unset($tempList[$key]);
                }
            }
            return $tempList;
        }
        return [];
    }

    private function sendWebHook()
    {
        if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
            $this->api->sendWebHook(
                '*Admin IPs list has been updated*'
            );
        }
    }

    /**
     * @param $aclItems
     * @param $aclId
     * @throws LocalizedException
     */
    private function deleteIps($aclItems, $aclId)
    {
        foreach ($aclItems as $key => $value) {
            $this->api->deleteAclItem($aclId, $value->id);
        }
    }
}
