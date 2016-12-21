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
     * Statistic constructor.
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Fastly\Cdn\Model\Config $config
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation
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
    protected function _prepareGAReqData()
    {
        if(!empty($this->_GAReqData)) {
            return $this->_GAReqData;
        }

        $mandatoryReqData = [];
        // Protocol version
        $mandatoryReqData['v'] = 1;
        // Tracking ID
        $mandatoryReqData['tid'] = $this->getGATrackingId();
        // Client ID
        $cid = $this->_config->getCID();
        $mandatoryReqData['cid'] = $cid;
        $mandatoryReqData['uid'] = $cid;

        // Default Website Name
        $websites = $this->_storeManager->getWebsites();

        $websiteName = 'Not set.';

        foreach($websites as $website)
        {
            if($website->getIsDefault()) {
                $websiteName = $website->getName();
            }
        }

        $mandatoryReqData['siteName'] = $websiteName;

        // Default website location info
        $siteLocation = '';
        $countryId = $this->_scopeConfig->getValue('general/store_information/country_id');
        if($countryId) {
            $country = $this->_countryInformation->getCountryInfo($countryId);
            $countryname = $country->getFullNameLocale();
        } else {
            $countryname = 'Country unknown';
        }
        $siteLocation = $countryname;

        $zip_code = $this->_scopeConfig->getValue('general/store_information/zip_code');

        $mandatoryReqData['siteLocation'] = $siteLocation;

        // Site domain
        $mandatoryReqData['siteDomain'] = $_SERVER['HTTP_HOST'];

        // Magento version & subversion
        $subVersion = $this->_metaData->getVersion();
        $version = (string)floor($subVersion);
        $mandatoryReqData['magentoVersion'] = $version;
        $mandatoryReqData['magentoSubVersion'] = $subVersion;

        $this->_GAReqData = $mandatoryReqData;

        return $this->_GAReqData;
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

    }

    protected function _sendReqToGA($uri, $method = \Zend_Http_Client::POST, $body = '')
    {
        $mandatoryGAData = (array)$this->getGAReqData();
    }

}