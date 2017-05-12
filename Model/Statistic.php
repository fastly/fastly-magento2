<?php

namespace Fastly\Cdn\Model;

use Magento\Framework\HTTP\Adapter\CurlFactory;
use Fastly\Cdn\Model\Config;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;

class Statistic extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * Fastly INSTALLED Flag
     */
    const FASTLY_INSTALLED_FLAG = 'installed';
    /**
     * Fastly CONFIGURED Flag
     */
    const FASTLY_CONFIGURED_FLAG = 'configured';
    /**
     * Fastly NOT_CONFIGURED Flag
     */
    const FASTLY_NOT_CONFIGURED_FLAG = 'not_configured';
    const FASTLY_VALIDATED_FLAG = 'validated';
    const FASTLY_NON_VALIDATED_FLAG = 'non_validated';

    const FASTLY_CONFIGURATION_FLAG = 'configuration';
    const FASTLY_VALIDATION_FLAG = 'validation';

    /**
     * Fastly upgrade flag
     */
    const FASTLY_UPGRADE_FLAG = 'upgrade';
    const FASTLY_UPGRADED_FLAG = 'upgraded';

    const FASTLY_MODULE_NAME = 'Fastly_Cdn';
    const CACHE_TAG = 'fastly_cdn_statistic';
    const FASTLY_GA_TRACKING_ID = 'UA-89025888-1';
    const GA_API_ENDPOINT = 'https://www.google-analytics.com/collect';
    const GA_HITTYPE_PAGEVIEW = 'pageview';
    const GA_HITTYPE_EVENT = 'event';
    const GA_PAGEVIEW_URL = 'http://fastly.com/';
    const GA_FASTLY_SETUP = 'Fastly Setup';

    protected $_GAReqData = [];
    protected $_validationServiceId = null;

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
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;

    /**
     * @var Api
     */
    protected $_api;

    /**
     * @var CurlFactory
     */
    protected $_curlFactory;

    /**
     * @var StatisticRepository
     */
    protected $_statisticRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $_countryFactory;

    /**
     * @var \Fastly\Cdn\Helper\Data
     */
    protected $_helper;


    /**
     * Statistic constructor.
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Fastly\Cdn\Model\Config $config
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param Api $api
     * @param CurlFactory $curlFactory
     * @param StatisticRepository $statisticRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
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
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Fastly\Cdn\Model\Api $api,
        CurlFactory $curlFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Fastly\Cdn\Model\StatisticRepository $statisticRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Fastly\Cdn\Helper\Data $helper,
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
        $this->_regionFactory = $regionFactory;
        $this->_api = $api;
        $this->_curlFactory = $curlFactory;
        $this->_statisticRepository = $statisticRepository;
        $this->_dateTime = $dateTime;
        $this->_countryFactory = $countryFactory;
        $this->_helper = $helper;

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

    /**
     * Returns GA API Endpoint
     *
     * @return string
     */
    public function getApiEndpoint()
    {
        return self::GA_API_ENDPOINT;
    }

    /**
     * Returns GA Tracking ID
     *
     * @return string
     */
    public function getGATrackingId()
    {
        return self::FASTLY_GA_TRACKING_ID;
    }

    /**
     * Prepares GA data for request
     *
     * @param array $additionalParams
     * @return array
     */
    protected function _prepareGAReqData(array $additionalParams = [])
    {
        if(!empty($this->_GAReqData)) {
            return $this->_GAReqData;
        }

        $mandatoryReqData = [];
        $mandatoryReqData['v'] = 1;
        // Tracking ID
        $mandatoryReqData['tid'] = $this->getGATrackingId();
        $cid = $this->_config->getCID();
        $mandatoryReqData['cid'] = $cid;
        $mandatoryReqData['uid'] = $cid;
        // Magento version
        $mandatoryReqData['ua'] = $this->_metaData->getVersion();
        // Get Default Country
        $mandatoryReqData['geoid'] = $this->getCountry();
        // Data Source parameter is used to filter spam hits
        $mandatoryReqData['ds'] = 'Fastly';

        $customVars = $this->_prepareCustomVariables();
        $this->_GAReqData = array_merge($mandatoryReqData, $customVars);

        return $this->_GAReqData;
    }

    /**
     * Returns Website name
     *
     * @return string $websiteName
     */
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

    /**
     * Checks if API token is valid
     *
     * @return bool $isApiKeyValid
     */
    public function isApiKeyValid()
    {
        $apiKey = $this->_scopeConfig->getValue(Config::XML_FASTLY_API_KEY);
        $serviceId = $this->_scopeConfig->getValue(Config::XML_FASTLY_SERVICE_ID);
        $isApiKeyValid = $this->_api->checkServiceDetails(true, $serviceId, $apiKey);

        return (bool)$isApiKeyValid;
    }

    /**
     * Prepares GA custom variables
     *
     * @return array
     */
    protected function _prepareCustomVariables()
    {
        if ($this->_validationServiceId != null) {
            $serviceId = $this->_validationServiceId;
        } else {
            $serviceId = $this->_scopeConfig->getValue(Config::XML_FASTLY_SERVICE_ID);
        }

        $customVars =  [
            // Service ID
            'cd1'   =>  $serviceId,
            // isAPIKeyValid
            'cd2'   =>  ($this->isApiKeyValid()) ? 'yes' : 'no',
            // Website name
            'cd3'   =>  $this->getWebsiteName(),
            // Site domain
            'cd4'   =>  $_SERVER['HTTP_HOST'],
            // Site location
            'cd5'   =>  $this->getSiteLocation(),
            // Fastly module version
            'cd6'   =>  $this->_helper->getModuleVersion(),
            // Fastly CID
            'cd7'   =>  $this->_config->getCID(),
            // Anti spam protection
            'cd8'   =>  'fastlyext'
        ];

        return $customVars;
    }

    /**
     * Returns default Country
     *
     * @return string
     */
    public function getCountry()
    {
        $countryCode = $this->_scopeConfig->getValue('general/country/default');
        if(!$countryCode)
        {
            return null;
        }

        $country = $this->_countryFactory->create()->loadByCode($countryCode);

        return $country->getName();
    }

    /**
     * Get Default Site Location
     *
     * @return string
     */
    public function getSiteLocation()
    {
        $countryId = $this->_scopeConfig->getValue('general/store_information/country_id');
        if($countryId) {
            $country = $this->_countryInformation->getCountryInfo($countryId);
            $countryName = $country->getFullNameEnglish();
        } else {
            $countryName = 'Unknown country';
        }

        $regionId = $this->_scopeConfig->getValue('general/store_information/region_id');
        $regionName = 'Unknown region';
        if($regionId) {
            $region = $this->_regionFactory->create();
            $region = $region->load($regionId);
            if($region->getId()) {
                $regionName = $region->getName();
            }
        }

        $postCode = $this->_scopeConfig->getValue('general/store_information/postcode');
        if(!$postCode) {
            $postCode = 'Unknown zip code';
        }

        return $countryName .' | '.$regionName.' | '.$postCode;
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

    /**
     * Sends request to GA that the Fastly module is installed
     *
     * @return bool|string $result
     */
    public function sendInstalledReq()
    {
        $pageViewParams = [
            'dl'    =>  self::GA_PAGEVIEW_URL . self::FASTLY_INSTALLED_FLAG,
            'dh'    =>  preg_replace('#^https?://#', '', rtrim(self::GA_PAGEVIEW_URL,'/')),
            'dp'    =>  '/'.self::FASTLY_INSTALLED_FLAG,
            'dt'    =>  ucfirst(self::FASTLY_INSTALLED_FLAG),
            't'     =>  self::GA_HITTYPE_PAGEVIEW,
        ];

        $this->_sendReqToGA($pageViewParams, self::GA_HITTYPE_PAGEVIEW);

        $eventParams = [
            'ec'    =>  self::GA_FASTLY_SETUP,
            'ea'    =>  'Fastly '.self::FASTLY_INSTALLED_FLAG,
            'el'    =>  $this->getWebsiteName(),
            'ev'    =>  0,
            't'     =>  self::GA_HITTYPE_EVENT
        ];

        $result = $this->_sendReqToGA(array_merge($pageViewParams, $eventParams));

        return $result;
    }

    /**
     * Sends request to GA every time the Test connection button is pressed
     *
     * @param $validatedFlag
     * @param null $serviceId
     * @return bool
     */
    public function sendValidationRequest($validatedFlag, $serviceId = null)
    {
        if ($serviceId != null) {
            $this->_validationServiceId = $serviceId;
        }

        if($validatedFlag) {
            $validationState = self::FASTLY_VALIDATED_FLAG;
        } else {
            $validationState = self::FASTLY_NON_VALIDATED_FLAG;
        }

        $pageViewParams = [
            'dl'    =>  self::GA_PAGEVIEW_URL . $validationState,
            'dh'    =>  preg_replace('#^https?://#', '', rtrim(self::GA_PAGEVIEW_URL,'/')),
            'dp'    =>  '/'.$validationState,
            'dt'    =>  ucfirst($validationState),
            't'     =>  self::GA_HITTYPE_PAGEVIEW,
        ];

        $this->_sendReqToGA($pageViewParams);

        $eventParams = [
            'ec'    =>  self::GA_FASTLY_SETUP,
            'ea'    =>  'Fastly '.$validationState,
            'el'    =>  $this->getWebsiteName(),
            'ev'    =>  $this->daysFromInstallation(),
            't'     =>  self::GA_HITTYPE_EVENT
        ];

        $result = $this->_sendReqToGA(array_merge($pageViewParams, $eventParams));

        return $result;
    }

    public function sendUpgradeRequest()
    {
        $pageViewParams = [
            'dl'    =>  self::GA_PAGEVIEW_URL . self::FASTLY_UPGRADED_FLAG,
            'dh'    =>  preg_replace('#^https?://#', '', rtrim(self::GA_PAGEVIEW_URL,'/')),
            'dp'    =>  '/'.self::FASTLY_UPGRADED_FLAG,
            'dt'    =>  ucfirst(self::FASTLY_UPGRADED_FLAG),
            't'     =>  self::GA_HITTYPE_PAGEVIEW,
        ];

        $this->_sendReqToGA($pageViewParams);

        $eventParams = [
            'ec'    =>  self::GA_FASTLY_SETUP,
            'ea'    =>  'Fastly '.self::FASTLY_UPGRADED_FLAG,
            'el'    =>  $this->getWebsiteName(),
            'ev'    =>  $this->daysFromInstallation(),
            't'     =>  self::GA_HITTYPE_EVENT
        ];

        $result = $this->_sendReqToGA(array_merge($pageViewParams, $eventParams));

        return $result;
    }

    /**
     * Sends Fastly configured\not_configured request to GA
     *
     * @param $configuredFlag
     * @return bool
     */
    public function sendConfigurationRequest($configuredFlag)
    {
        if($configuredFlag) {
            $configuredState = self::FASTLY_CONFIGURED_FLAG;
        } else {
            $configuredState = self::FASTLY_NOT_CONFIGURED_FLAG;
        }

        $pageViewParams = [
            'dl'    =>  self::GA_PAGEVIEW_URL . $configuredState,
            'dh'    =>  preg_replace('#^https?://#', '', rtrim(self::GA_PAGEVIEW_URL,'/')),
            'dp'    =>  '/'.$configuredState,
            'dt'    =>  ucfirst($configuredState),
            't'     =>  self::GA_HITTYPE_PAGEVIEW,
        ];

        $this->_sendReqToGA($pageViewParams);

        $eventParams = [
            'ec'    =>  self::GA_FASTLY_SETUP,
            'ea'    =>  'Fastly '.$configuredState,
            'el'    =>  $this->getWebsiteName(),
            'ev'    =>  $this->daysFromInstallation(),
            't'     =>  self::GA_HITTYPE_EVENT
        ];

        $result = $this->_sendReqToGA(array_merge($pageViewParams, $eventParams));

        return $result;
    }

    /**
     * Calculates number of days since Fastly module installation
     *
     * @return mixed|null
     */
    public function daysFromInstallation()
    {
        $stat = $this->_statisticRepository->getStatByAction(self::FASTLY_INSTALLED_FLAG);

        if(!$stat->getCreatedAt()) {
            return null;
        }
        $installDate = date_create($stat->getCreatedAt());
        $currentDate = date_create($this->_dateTime->gmtDate());

        $dateDiff = date_diff($installDate, $currentDate);

        return $dateDiff->days;
    }

    /**
     * Sends CURL request to GA
     *
     * @param string $body
     * @param string $method
     * @param string $uri
     * @return bool
     */
    protected function _sendReqToGA($body = '', $method = \Zend_Http_Client::POST, $uri = self::GA_API_ENDPOINT)
    {
        $reqGAData = (array)$this->getGAReqData();

        if($body != '' && is_array($body) && !empty($body)) {
            $body = array_merge($reqGAData, $body);
        }

        try {
            $client = $this->_curlFactory->create();
            $client->addOption(CURLOPT_TIMEOUT, 10);
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
            return false;
        }
    }

}