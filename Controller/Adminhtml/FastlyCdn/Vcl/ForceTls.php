<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;

class ForceTls extends Action
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
     * @var \Fastly\Cdn\Model\Api
     */
    private $api;

    /**
     * @var Vcl
     */
    private $vcl;

    /**
     * ForceTls constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;

        parent::__construct($context);
    }

    /**
     * Upload VCL snippets
     *
     * @return $resultJsonFactory
     */
    public function execute()
    {
        try {
            $result = $this->resultJson->create();
            $activeVersion = $this->getRequest()->getParam('active_version');
            $activateVcl = $this->getRequest()->getParam('activate_flag');
            $service = $this->api->checkServiceDetails();

            if (!$service) {
                return $result->setData([
                    'status'    => false,
                    'msg'   => 'Failed to check Service details.'
                ]);
            }

            $currActiveVersion = $this->vcl->determineVersions($service->versions);

            if ($currActiveVersion['active_version'] != $activeVersion) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Active versions mismatch.'
                ]);
            }

            $clone = $this->api->cloneVersion($currActiveVersion['active_version']);

            if (!$clone) {
                return $result->setData([
                    'status'    => false,
                    'msg'   => 'Failed to clone active version.'
                ]);
            }
            $reqName = Config::FASTLY_MAGENTO_MODULE.'_force_tls';
            $checkIfReqExist = $this->api->getRequest($activeVersion, $reqName);
            $snippet = $this->config->getVclSnippets('/vcl_snippets_force_tls', 'recv.vcl');

            if (!$checkIfReqExist) {
                $request = [
                    'name'          => $reqName,
                    'service_id'    => $service->id,
                    'version'       => $currActiveVersion['active_version'],
                    'force_ssl'     => true
                ];

                $createReq = $this->api->createRequest($clone->number, $request);

                if (!$createReq) {
                    return $result->setData([
                        'status'    => false,
                        'msg'       => 'Failed to create the REQUEST object.'
                    ]);
                }

                // Add force TLS snipet
                foreach ($snippet as $key => $value) {
                    $snippetData = [
                        'name'      => Config::FASTLY_MAGENTO_MODULE . '_force_tls_' . $key,
                        'type'      => $key, 'dynamic' => "0",
                        'priority'  => 10,
                        'content'   => $value
                    ];
                    $status = $this->api->uploadSnippet($clone->number, $snippetData);

                    if (!$status) {
                        return $result->setData([
                            'status'    => false,
                            'msg'       => 'Failed to upload the Snippet file.'
                        ]);
                    }
                }
            } else {
                $deleteRequest = $this->api->deleteRequest($clone->number, $reqName);

                if (!$deleteRequest) {
                    return $result->setData([
                        'status'    => false,
                        'msg'       => 'Failed to delete the REQUEST object.'
                    ]);
                }

                // Remove force TLS snipet
                foreach ($snippet as $key => $value) {
                    $name = Config::FASTLY_MAGENTO_MODULE.'_force_tls_'.$key;
                    $status = $this->api->removeSnippet($clone->number, $name);

                    if (!$status) {
                        return $result->setData([
                            'status'    => false,
                            'msg'       => 'Failed to remove the Snippet file.'
                        ]);
                    }
                }
            }

            $validate = $this->api->validateServiceVersion($clone->number);

            if ($validate->status == 'error') {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to validate service version: ' . $validate->msg
                ]);
            }

            if ($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }

            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                if ($checkIfReqExist) {
                    $this->api->sendWebHook('*Force TLS has been turned OFF in Fastly version '. $clone->number . '*');
                } else {
                    $this->api->sendWebHook('*Force TLS has been turned ON in Fastly version '. $clone->number . '*');
                }
            }

            return $result->setData(['status' => true]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
