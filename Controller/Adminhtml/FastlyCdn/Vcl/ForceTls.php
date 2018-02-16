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
        $result = $this->resultJson->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $activateVcl = $this->getRequest()->getParam('activate_flag');
            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $clone = $this->api->cloneVersion($currActiveVersion);
            $reqName = Config::FASTLY_MAGENTO_MODULE.'_force_tls';
            $checkIfReqExist = $this->api->getRequest($activeVersion, $reqName);
            $snippet = $this->config->getVclSnippets('/vcl_snippets_force_tls', 'recv.vcl');

            if (!$checkIfReqExist) {
                $request = [
                    'name'          => $reqName,
                    'service_id'    => $service->id,
                    'version'       => $currActiveVersion,
                    'force_ssl'     => true
                ];

                $this->api->createRequest($clone->number, $request);

                // Add force TLS snipet
                foreach ($snippet as $key => $value) {
                    $snippetData = [
                        'name'      => Config::FASTLY_MAGENTO_MODULE . '_force_tls_' . $key,
                        'type'      => $key,
                        'dynamic' => "0",
                        'priority'  => 10,
                        'content'   => $value
                    ];
                    $this->api->uploadSnippet($clone->number, $snippetData);
                }
            } else {
                $this->api->deleteRequest($clone->number, $reqName);

                // Remove force TLS snipet
                foreach ($snippet as $key => $value) {
                    $name = Config::FASTLY_MAGENTO_MODULE.'_force_tls_'.$key;
                    $this->api->removeSnippet($clone->number, $name);
                }
            }

            $this->api->validateServiceVersion($clone->number);

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
