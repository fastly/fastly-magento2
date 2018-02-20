<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Magento\Framework\Exception\LocalizedException;

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

    /**
     * Upload Blocking snippets
     *
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $service = $this->api->checkServiceDetails();
            $currActiveVersion = $this->getActiveVersion($service);

            $snippet = $this->config->getVclSnippets('/vcl_snippets_blocking', 'recv.vcl');

            $country_codes = $this->prepareCountryCodes($this->config->getBlockByCountry());
            $acls = $this->prepareAcls($this->config->getBlockByAcl());

            $blockedItems = $country_codes . $acls;
            $strippedBlockedItems = substr($blockedItems, 0, strrpos($blockedItems, '||', -1));

            // Add blocking snippet
            foreach ($snippet as $key => $value) {
                if ($strippedBlockedItems === '') {
                    $value = '';
                } else {
                    $value = str_replace('####BLOCKED_ITEMS####', $strippedBlockedItems, $value);
                }

                $snippetName = Config::FASTLY_MAGENTO_MODULE . '_blocking_' . $key;
                $snippetId = $this->api->getSnippet($currActiveVersion['active_version'], $snippetName)->id;
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

    /**
     * Get the current active version
     *
     * @param $service
     * @return array
     */
    private function getActiveVersion($service)
    {
        $currActiveVersion = $this->vcl->determineVersions($service->versions);
        return $currActiveVersion;
    }

    /**
     * Prepares ACLS VCL snippets
     *
     * @param $blockedAcls
     * @return string
     */
    private function prepareAcls($blockedAcls)
    {
        $result = '';

        if ($blockedAcls != null) {
            $blockedAclsPieces = explode(",", $blockedAcls);
            foreach ($blockedAclsPieces as $acl) {
                $result .= ' client.ip ~ ' . $acl . ' ||';
            }
        }

        return $result;
    }

    /**
     * Prepares blocked countries VCL snippet
     *
     * @param $blockedCountries
     * @return string
     */
    private function prepareCountryCodes($blockedCountries)
    {
        $result = '';

        if ($blockedCountries != null) {
            $blockedCountriesPieces = explode(",", $blockedCountries);
            foreach ($blockedCountriesPieces as $code) {
                $result .= ' client.geo.country_code == "' . $code . '" ||';
            }
        }

        return $result;
    }
}
