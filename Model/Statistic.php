<?php

namespace Fastly\Cdn\Model;

use Magento\Framework\HTTP\Adapter\CurlFactory;
use Fastly\Cdn\Model\Config;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;

class Statistic extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const FASTLY_INSTALLED_FLAG = 'installed';
    const FASTLY_CONFIGURED_FLAG = 'configured';
    const FASTLY_TEST_FLAG = 'test';
    const FASTLY_MODULE_NAME = 'Fastly_Cdn';
    const CACHE_TAG = 'fastly_cdn_statistic';
    const FASTLY_GA_TRACKING_ID = 'UA-89025888-2';
    const GA_API_ENDPOINT = 'https://www.google-analytics.com/collect';
    const GA_HITTYPE_PAGEVIEW = 'pageview';
    const GA_HITTYPE_EVENT = 'event';
    const GA_PAGEVIEW_URL = 'http://fastly.com/';

    protected $_GAReqData = [];

    /**
     * @var \Fastly\Cdn\Model\Config
     */
    protected $_config;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Magento meta data (version)
     *
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $_metaData;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Directory\Api\CountryInformationAcquirerInterface
     */
    protected $_countryInformation;

    /**
     * @var \Magento\Directory\Api\Data\RegionInformationInterface
     */
    protected $_regionInformation;

    /**
     * @var Api
     */
    protected $_api;

    /**
     * @var CurlFactory
     */
    protected $_curlFactory;

    /**
     * Statistic constructor.
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Fastly\Cdn\Model\Config $config
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation
     * @param \Magento\Directory\Api\Data\RegionInformationInterface $regionInformation
     * @param Api $api
     * @param CurlFactory $curlFactory
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation,
        \Magento\Directory\Api\Data\RegionInformationInterface $regionInformation,
        \Fastly\Cdn\Model\Api $api,
        CurlFactory $curlFactory,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->_config = $config;
        $this->_storeManager = $storeManager;
        $this->_metaData = $productMetadata;
        $this->_scopeConfig = $scopeConfig;
        $this->_countryInformation = $countryInformation;
        $this->_regionInformation = $regionInformation;
        $this->_api = $api;
        $this->_curlFactory = $curlFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Fastly\Cdn\Model\ResourceModel\Statistic');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG. '_' .$this->getId()];
    }

    public function getApiEndpoint()
    {
        return self::GA_API_ENDPOINT;
    }

    public function getGATrackingId()
    {
        return self::FASTLY_GA_TRACKING_ID;
    }

    /**
     * Prepares GA data for request
     *
     * @return array
     */
    protected function _prepareGAReqData(array $additionalParams = [])
    {
        if(!empty($this->_GAReqData)) {
            return $this->_GAReqData;
        }

        $mandatoryReqData = [];
        $mandatoryReqData['v'] = 1;
        $mandatoryReqData['tid'] = $this->getGATrackingId();
        $cid = $this->_config->getCID();
        $mandatoryReqData['cid'] = $cid;
        $mandatoryReqData['uid'] = $cid;
        // Magento version
        $mandatoryReqData['ua'] = $this->_metaData->getVersion();

        $countryId = $this->_scopeConfig->getValue('general/store_information/country_id');
        if($countryId) {
            $country = $this->_countryInformation->getCountryInfo($countryId);
            //$countryname = $country->getFullNameLocale();
            $countryCode = $country->getTwoLetterAbbreviation();
            // Country code
            $mandatoryReqData['geoid'] = $countryCode;
        }

        $customVars = $this->_prepareCustomVariables();

        $this->_GAReqData = array_merge($mandatoryReqData, $customVars);

        return $this->_GAReqData;
    }

    public function getWebsiteName()
    {
        $websites = $this->_storeManager->getWebsites();

        $websiteName = 'Not set.';

        foreach($websites as $website)
        {
            if($website->getIsDefault()) {
                $websiteName = $website->getName();
            }
        }

        return $websiteName;
    }

    public function isApiKeyValid()
    {
        $apiKey = $this->_scopeConfig->getValue(Config::XML_FASTLY_API_KEY);
        $serviceId = $this->_scopeConfig->getValue(Config::XML_FASTLY_SERVICE_ID);
        $isApiKeyValid = $this->_api->checkServiceDetails(true, $serviceId, $apiKey);

        return (bool)$isApiKeyValid;
    }

    protected function _prepareCustomVariables()
    {
        $customVars =  [
            // Service ID
            'cd1'   =>  $this->_scopeConfig->getValue(Config::XML_FASTLY_SERVICE_ID),
            // isAPIKeyValid
            'cd2'   =>  $this->isApiKeyValid(),
            // Website name
            'cd3'   =>  $this->getWebsiteName(),
            // Site domain
            'cd4'   =>  $_SERVER['HTTP_HOST']
        ];

        return $customVars;
    }

    /**
     * Generate GA CID
     *
     * @return string
     */
    public function generateCid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Get Google Analytics mandatory data
     *
     * @return array
     */
    public function getGAReqData()
    {
        return $this->_prepareGAReqData();
    }

    public function sendInstalledReq()
    {
        $pageViewParams = [
            'dl'    =>  self::GA_PAGEVIEW_URL . self::FASTLY_INSTALLED_FLAG,
            'dh'    =>  preg_replace('#^https?://#', '', rtrim(self::GA_PAGEVIEW_URL,'/')),
            'dp'    =>  '/'.self::FASTLY_INSTALLED_FLAG,
            'dt'    =>  ucfirst(self::FASTLY_INSTALLED_FLAG)
        ];

        $this->_sendReqToGA($pageViewParams);

        $eventParams = [
            'ec'    =>  'Fastly Setup',
            'ea'    =>  'Fastly '.self::FASTLY_INSTALLED_FLAG,
            'el'    =>  $this->getWebsiteName(),
            'ev'    =>  0
        ];

        $this->_sendReqToGA(array_merge($pageViewParams, $eventParams));
    }

    protected function _sendReqToGA($body = '', $method = \Zend_Http_Client::POST, $uri = self::GA_API_ENDPOINT)
    {
        $reqGAData = (array)$this->getGAReqData();

        if($body != '' && is_array($body) && !empty($body)) {
            $body = array_merge($reqGAData, $body);
        }

        try {
            $client = $this->_curlFactory->create();
            $client->write($method, $uri, '1.1', null, http_build_query($body));
            $response = $client->read();
            $responseBody = \Zend_Http_Response::extractBody($response);
            $responseCode = \Zend_Http_Response::extractCode($response);
            $client->close();

            if ($responseCode != '200') {
                throw new \Exception('Return status ' . $responseCode);
            }

            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

}