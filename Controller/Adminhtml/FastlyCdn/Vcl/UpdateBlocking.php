<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;

class UpdateBlocking extends Action
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
     * UpdateBlocking constructor.
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

    public function execute()
    {
        try {
            $result = $this->resultJson->create();

            $service = $this->api->checkServiceDetails();
            $versions = $this->vcl->determineVersions($service->versions);
            $activeVersion = $versions['active_version'];

            if (!$service) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to check Service details.'
                ]);
            }

            $currActiveVersion = $this->vcl->determineVersions($service->versions);
            if ($currActiveVersion['active_version'] != $activeVersion) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Active versions mismatch.'
                ]);
            }

//            $reqName = Config::FASTLY_MAGENTO_MODULE . '_blocking';
//            $checkIfReqExist = $this->api->getRequest($activeVersion, $reqName);
//            $snippet = $this->config->getVclSnippets('/vcl_snippets_blocking', 'recv.vcl');

            $blockedCountries = $this->config->getBlockByCountry();
            $blockedAcls = $this->config->getBlockByAcl();

            $country_codes = '';
            $acls = '';

            if ($blockedCountries != null) {
                $blockedCountriesPieces = explode(",", $blockedCountries);
                foreach ($blockedCountriesPieces as $code) {
                    $country_codes .= ' client.geo.country_code == "' . $code . '" ||';
                }
            }

            if ($blockedAcls != null) {
                $blockedAclsPieces = explode(",", $blockedAcls);
                foreach ($blockedAclsPieces as $acl) {
                    $acls .= ' client.ip ~ ' . $acl . ' ||';
                }
            }

            $snippet = $this->config->getVclSnippets('/vcl_snippets_blocking', 'recv.vcl');

            foreach ($snippet as $key => $value) {
                $snippetName = Config::FASTLY_MAGENTO_MODULE . '_blocking_' . $key;
                $snippetId = $this->api->getSnippet($activeVersion, $snippetName)->id;
                $params = [
                    'name' =>  $snippetId,
                    'content'   => $value
                ];

                $this->api->updateSnippet($params);
            }

            return $result->setData([
                'status' => true
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
