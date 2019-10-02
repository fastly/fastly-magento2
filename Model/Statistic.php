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
namespace Fastly\Cdn\Model;

use Fastly\Cdn\Helper\Data;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Request\Http;

/**
 * Class Statistic
 *
 * @package Fastly\Cdn\Model
 */
class Statistic extends AbstractModel implements IdentityInterface
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
    /**
     * @var array
     */
    private $GAReqData = [];
    /**
     * @var null|string
     */
    private $validationServiceId = null;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ProductMetadataInterface Magento meta data (version)
     */
    private $metaData;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var CountryInformationAcquirerInterface
     */
    private $countryInformation;
    /**
     * @var RegionFactory
     */
    private $regionFactory;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var CurlFactory
     */
    private $curlFactory;
    /**
     * @var StatisticRepository
     */
    private $statisticRepository;
    /**
     * @var DateTime
     */
    private $dateTime;
    /**
     * @var Http
     */
    private $request;
    /**
     * @var CountryFactory
     */
    private $countryFactory;
    /**
     * @var Data
     */
    private $helper;

    /**
     * Statistic constructor.
     * @param Context $context
     * @param Registry $registry
     * @param \Fastly\Cdn\Model\Config $config
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param CountryInformationAcquirerInterface $countryInformation
     * @param RegionFactory $regionFactory
     * @param Api $api
     * @param CurlFactory $curlFactory
     * @param StatisticRepository $statisticRepository
     * @param DateTime $dateTime
     * @param ProductMetadataInterface $productMetadata
     * @param Http $request
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        Registry $registry,
        Config $config,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        CountryInformationAcquirerInterface $countryInformation,
        RegionFactory $regionFactory,
        Api $api,
        CurlFactory $curlFactory,
        CountryFactory $countryFactory,
        StatisticRepository $statisticRepository,
        DateTime $dateTime,
        Data $helper,
        ProductMetadataInterface $productMetadata,
        Http $request,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->metaData = $productMetadata;
        $this->scopeConfig = $scopeConfig;
        $this->countryInformation = $countryInformation;
        $this->regionFactory = $regionFactory;
        $this->api = $api;
        $this->curlFactory = $curlFactory;
        $this->statisticRepository = $statisticRepository;
        $this->dateTime = $dateTime;
        $this->countryFactory = $countryFactory;
        $this->helper = $helper;
        $this->request = $request;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct() // @codingStandardsIgnoreLine - required by parent class
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
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function prepareGAReqData()
    {
        if (!empty($this->GAReqData)) {
            return $this->GAReqData;
        }

        $mandatoryReqData = [];
        $mandatoryReqData['v'] = 1;
        // Tracking ID
        $mandatoryReqData['tid'] = $this->getGATrackingId();
        $cid = $this->config->getCID();
        $mandatoryReqData['cid'] = $cid;
        $mandatoryReqData['uid'] = $cid;
        // Magento version
        $mandatoryReqData['ua'] = $this->metaData->getVersion();
        // Get Default Country
        $mandatoryReqData['geoid'] = $this->getCountry();
        // Data Source parameter is used to filter spam hits
        $mandatoryReqData['ds'] = 'Fastly';

        $customVars = $this->prepareCustomVariables();
        $this->GAReqData = array_merge($mandatoryReqData, $customVars);

        return $this->GAReqData;
    }

    /**
     * Returns Website name
     *
     * @return string $websiteName
     */
    public function getWebsiteName()
    {
        $websites = $this->storeManager->getWebsites();

        $websiteName = 'Not set.';

        foreach ($websites as $website) {
            if ($website->getIsDefault()) {
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
        try {
            $apiKey = $this->scopeConfig->getValue(Config::XML_FASTLY_API_KEY);
            $serviceId = $this->scopeConfig->getValue(Config::XML_FASTLY_SERVICE_ID);
            $isApiKeyValid = $this->api->checkServiceDetails(true, $serviceId, $apiKey);
        } catch (\Exception $e) {
            return false;
        }
        return (bool)$isApiKeyValid;
    }

    /**
     * Prepares GA custom variables
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function prepareCustomVariables()
    {
        if ($this->validationServiceId != null) {
            $serviceId = $this->validationServiceId;
        } else {
            $serviceId = $this->scopeConfig->getValue(Config::XML_FASTLY_SERVICE_ID);
        }

        $customVars =  [
            // Service ID
            'cd1'   =>  $serviceId,
            // isAPIKeyValid
            'cd2'   =>  ($this->isApiKeyValid()) ? 'yes' : 'no',
            // Website name
            'cd3'   =>  $this->getWebsiteName(),
            // Site domain
            'cd4'   =>  $this->request->getServer('HTTP_HOST'),
            // Site location
            'cd5'   =>  $this->getSiteLocation(),
            // Fastly module version
            'cd6'   =>  $this->helper->getModuleVersion(),
            // Fastly CID
            'cd7'   =>  $this->config->getCID(),
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
        $countryCode = $this->scopeConfig->getValue('general/country/default');
        if (!$countryCode) {
            return null;
        }

        $country = $this->countryFactory->create()->loadByCode($countryCode);

        return $country->getName();
    }

    /**
     * Get Default Site Location
     *
     * @return string
     */
    public function getSiteLocation()
    {
        $countryId = $this->scopeConfig->getValue('general/store_information/country_id');
        if ($countryId) {
            $country = $this->countryFactory->create()->loadByCode($countryId);
            $countryName = $country->getName();
        } else {
            $countryName = 'Unknown country';
        }

        $regionId = $this->scopeConfig->getValue('general/store_information/region_id');
        $regionName = 'Unknown region';
        if ($regionId) {
            $region = $this->regionFactory->create();
            $region = $region->load($regionId);
            if ($region->getId()) {
                $regionName = $region->getName();
            }
        }

        $postCode = $this->scopeConfig->getValue('general/store_information/postcode');
        if (!$postCode) {
            $postCode = 'Unknown zip code';
        }

        return $countryName .' | '.$regionName.' | '.$postCode;
    }

    /**
     * Generate GA CID
     *
     * @return string
     */
    public function generateCid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            /* 32 bits for time_low */
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            /* 16 bits for time_mid */
            random_int(0, 0xffff),
            /* 16 bits for time_hi_and_version,
               four most significant bits holds version number 4 */
            random_int(0, 0x0fff) | 0x4000,
            /* 16 bits, 8 bits for clk_seq_hi_res,
               8 bits for clk_seq_low,
               two most significant bits holds zero and one for variant DCE1.1 */
            random_int(0, 0x3fff) | 0x8000,
            // 48 bits for node
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    /**
     * Get Google Analytics mandatory data
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getGAReqData()
    {
        return $this->prepareGAReqData();
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
            'dh'    =>  preg_replace('#^https?://#', '', rtrim(self::GA_PAGEVIEW_URL, '/')),
            'dp'    =>  '/'.self::FASTLY_INSTALLED_FLAG,
            'dt'    =>  ucfirst(self::FASTLY_INSTALLED_FLAG),
            't'     =>  self::GA_HITTYPE_PAGEVIEW,
        ];

        $this->sendReqToGA($pageViewParams, self::GA_HITTYPE_PAGEVIEW);

        $eventParams = [
            'ec'    =>  self::GA_FASTLY_SETUP,
            'ea'    =>  'Fastly '.self::FASTLY_INSTALLED_FLAG,
            'el'    =>  $this->getWebsiteName(),
            'ev'    =>  0,
            't'     =>  self::GA_HITTYPE_EVENT
        ];

        $result = $this->sendReqToGA(array_merge($pageViewParams, $eventParams));

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
            $this->validationServiceId = $serviceId;
        }

        if ($validatedFlag) {
            $validationState = self::FASTLY_VALIDATED_FLAG;
        } else {
            $validationState = self::FASTLY_NON_VALIDATED_FLAG;
        }

        $pageViewParams = [
            'dl'    =>  self::GA_PAGEVIEW_URL . $validationState,
            'dh'    =>  preg_replace('#^https?://#', '', rtrim(self::GA_PAGEVIEW_URL, '/')),
            'dp'    =>  '/'.$validationState,
            'dt'    =>  ucfirst($validationState),
            't'     =>  self::GA_HITTYPE_PAGEVIEW,
        ];

        $this->sendReqToGA($pageViewParams);

        $eventParams = [
            'ec'    =>  self::GA_FASTLY_SETUP,
            'ea'    =>  'Fastly '.$validationState,
            'el'    =>  $this->getWebsiteName(),
            'ev'    =>  $this->daysFromInstallation(),
            't'     =>  self::GA_HITTYPE_EVENT
        ];

        $result = $this->sendReqToGA(array_merge($pageViewParams, $eventParams));

        return $result;
    }

    public function sendUpgradeRequest()
    {
        $pageViewParams = [
            'dl'    =>  self::GA_PAGEVIEW_URL . self::FASTLY_UPGRADED_FLAG,
            'dh'    =>  preg_replace('#^https?://#', '', rtrim(self::GA_PAGEVIEW_URL, '/')),
            'dp'    =>  '/'.self::FASTLY_UPGRADED_FLAG,
            'dt'    =>  ucfirst(self::FASTLY_UPGRADED_FLAG),
            't'     =>  self::GA_HITTYPE_PAGEVIEW,
        ];

        $this->sendReqToGA($pageViewParams);

        $eventParams = [
            'ec'    =>  self::GA_FASTLY_SETUP,
            'ea'    =>  'Fastly '.self::FASTLY_UPGRADED_FLAG,
            'el'    =>  $this->getWebsiteName(),
            'ev'    =>  $this->daysFromInstallation(),
            't'     =>  self::GA_HITTYPE_EVENT
        ];

        $result = $this->sendReqToGA(array_merge($pageViewParams, $eventParams));

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
        if ($configuredFlag) {
            $configuredState = self::FASTLY_CONFIGURED_FLAG;
        } else {
            $configuredState = self::FASTLY_NOT_CONFIGURED_FLAG;
        }

        $pageViewParams = [
            'dl'    =>  self::GA_PAGEVIEW_URL . $configuredState,
            'dh'    =>  preg_replace('#^https?://#', '', rtrim(self::GA_PAGEVIEW_URL, '/')),
            'dp'    =>  '/'.$configuredState,
            'dt'    =>  ucfirst($configuredState),
            't'     =>  self::GA_HITTYPE_PAGEVIEW,
        ];

        $this->sendReqToGA($pageViewParams);

        $eventParams = [
            'ec'    =>  self::GA_FASTLY_SETUP,
            'ea'    =>  'Fastly '.$configuredState,
            'el'    =>  $this->getWebsiteName(),
            'ev'    =>  $this->daysFromInstallation(),
            't'     =>  self::GA_HITTYPE_EVENT
        ];

        $result = $this->sendReqToGA(array_merge($pageViewParams, $eventParams));

        return $result;
    }

    /**
     * Calculates number of days since Fastly module installation
     *
     * @return mixed|null
     */
    public function daysFromInstallation()
    {
        $stat = $this->statisticRepository->getStatByAction(self::FASTLY_INSTALLED_FLAG);

        if (!$stat->getCreatedAt()) {
            return null;
        }
        $installDate = date_create($stat->getCreatedAt());
        $currentDate = date_create($this->dateTime->gmtDate());

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
    private function sendReqToGA($body = '', $method = \Zend_Http_Client::POST, $uri = self::GA_API_ENDPOINT)
    {
        $reqGAData = (array)$this->getGAReqData();

        if ($body != '' && is_array($body) && !empty($body)) {
            $body = array_merge($reqGAData, $body);
        }

        try {
            $client = $this->curlFactory->create();
            $client->addOption(CURLOPT_TIMEOUT, 10);
            $client->write($method, $uri, '1.1', null, http_build_query($body));
            $response = $client->read();
            $responseCode = \Zend_Http_Response::extractCode($response);
            $client->close();

            if ($responseCode != '200') {
                throw new LocalizedException(__('Return status ' . $responseCode));
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
