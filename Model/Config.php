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

use Magento\Framework\Filesystem;
use Magento\Framework\Module\Dir;

/**
 * Model is responsible for replacing default vcl template
 * file configuration with user-defined from configuration
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */

/**
 * Class Config
 *
 */
class Config extends \Magento\PageCache\Model\Config
{
    /**
     * Cache types
     */
    const FASTLY = 'fastly';

    /**
     * Magento module prefix used for naming vcl snippets, condition and request
     */
    const FASTLY_MAGENTO_MODULE = 'magentomodule';

    /**
     * Magento Error Page Response Object Name
     */
    const ERROR_PAGE_RESPONSE_OBJECT = 'magentomodule_error_page_response_object';

    /**
     * GeoIP action "dialog"
     */
    const GEOIP_ACTION_DIALOG = 'dialog';

    /**
     * GeoIP action "redirect"
     */
    const GEOIP_ACTION_REDIRECT = 'redirect';

    /**
     * GeoIP processed cookie name
     */
    const GEOIP_PROCESSED_COOKIE_NAME = 'FASTLY_CDN_GEOIP_PROCESSED';

    /**
     * XML path to Fastly config template path
     */
    const FASTLY_CONFIGURATION_PATH = 'system/full_page_cache/fastly/path';

    /**
     * Path to Fastly service ID
     */
    const FASTLY_API_ENDPOINT = 'https://api.fastly.com/';

    /**
     * XML path to Fastly service ID
     */
    const XML_FASTLY_SERVICE_ID = 'system/full_page_cache/fastly/fastly_service_id';

    /**
     * XML path to Fastly API token
     */
    const XML_FASTLY_API_KEY = 'system/full_page_cache/fastly/fastly_api_key';

    /**
     * XML path to stale ttl path
     */
    const XML_FASTLY_STALE_TTL = 'system/full_page_cache/fastly/fastly_advanced_configuration/stale_ttl';

    /**
     * config path to basic auth status
     */
    const FASTLY_BASIC_AUTH_ENABLE = 'system/full_page_cache/fastly/fastly_basic_auth/enable_basic_auth';

    /**
     * XML path to stale error ttl path
     */
    const XML_FASTLY_STALE_ERROR_TTL = 'system/full_page_cache/fastly/fastly_advanced_configuration/stale_error_ttl';

    /**
     * XML path to Fastly admin path timeout
     */
    const XML_FASTLY_ADMIN_PATH_TIMEOUT
        = 'system/full_page_cache/fastly/fastly_advanced_configuration/admin_path_timeout';

    /**
     * XML path to Fastly ignored url parameters
     */
    const XML_FASTLY_IGNORED_URL_PARAMETERS
        = 'system/full_page_cache/fastly/fastly_advanced_configuration/ignored_url_parameters';

    /**
     * XML path to X-Magento-Tags size value
     */

    const XML_FASTLY_X_MAGENTO_TAGS_SIZE
        = 'system/full_page_cache/fastly/fastly_advanced_configuration/x_magento_tags_size';

    /**
     * XML path to purge catalog category
     */
    const XML_FASTLY_PURGE_CATALOG_CATEGORY
        = 'system/full_page_cache/fastly/fastly_advanced_configuration/purge_catalog_category';

    /**
     * XML path to purge catalog product
     */
    const XML_FASTLY_PURGE_CATALOG_PRODUCT
        = 'system/full_page_cache/fastly/fastly_advanced_configuration/purge_catalog_product';

    /**
     * XML path to purge CMS page
     */
    const XML_FASTLY_PURGE_CMS_PAGE = 'system/full_page_cache/fastly/fastly_advanced_configuration/purge_cms_page';

    /**
     * XML path to config preserve_static
     */
    const XML_FASTLY_PRESERVE_STATIC = 'system/full_page_cache/fastly/fastly_advanced_configuration/preserve_static';

    /**
     * XML path to soft purge
     */
    const XML_FASTLY_SOFT_PURGE = 'system/full_page_cache/fastly/fastly_advanced_configuration/soft_purge';

    /**
     * XML path to enable GeoIP
     */
    const XML_FASTLY_GEOIP_ENABLED = 'system/full_page_cache/fastly/fastly_advanced_configuration/enable_geoip';

    /**
     * XML path to GeoIP action
     */
    const XML_FASTLY_GEOIP_ACTION = 'system/full_page_cache/fastly/fastly_advanced_configuration/geoip_action';

    /**
     * XML path to GeoIP redirect mapping
     */
    const XML_FASTLY_GEOIP_COUNTRY_MAPPING
        = 'system/full_page_cache/fastly/fastly_advanced_configuration/geoip_country_mapping';

    /**
     * XML path to image optimizations flag
     */
    const XML_FASTLY_IMAGE_OPTIMIZATIONS
        = 'system/full_page_cache/fastly/fastly_image_optimization_configuration/image_optimizations';

    /**
     * XML path to image optimizations pixel ratio flag
     */
    const XML_FASTLY_IMAGE_OPTIMIZATIONS_PIXEL_RATIO
        = 'system/full_page_cache/fastly/fastly_image_optimization_configuration/image_optimizations_pixel_ratio';

    /**
     * XML path to image optimizations pixel ratios
     */
    const XML_FASTLY_IMAGE_OPTIMIZATIONS_RATIOS
        = 'system/full_page_cache/fastly/fastly_image_optimization_configuration/image_optimizations_ratios';

    /**
     * XML path to Google analytics CID
     */
    const XML_FASTLY_GA_CID = 'system/full_page_cache/fastly/fastly_ga_cid';

    /**
     * XML path to Last checked issued Fastly M2 version
     */
    const XML_FASTLY_LAST_CHECKED_ISSUED_VERSION = 'system/full_page_cache/fastly/last_checked_issues_version';

    /**
     * XML path to Fastly module version
     */
    const XML_FASTLY_MODULE_VERSION = 'system/full_page_cache/fastly/current_version';

    /**
     * XML path to Fastly list of blocked countries
     */
    const XML_FASTLY_BLOCK_BY_COUNTRY = 'system/full_page_cache/fastly/fastly_blocking/block_by_country';

    /**
     * XML path to Fastly list of blocked Acls
     */
    const XML_FASTLY_BLOCK_BY_ACL = 'system/full_page_cache/fastly/fastly_blocking/block_by_acl';

    /**
     * XML path to enable Webhooks
     */
    const XML_FASTLY_WEBHOOKS_ENABLED = 'system/full_page_cache/fastly/fastly_web_hooks/enable_webhooks';

    /**
     * XML path to Webhook Username
     */
    const XML_FASTLY_WEBHOOKS_USERNAME = 'system/full_page_cache/fastly/fastly_web_hooks/webhooks_username';

    /**
     * XML path to Incoming webhook URL
     */
    const XML_FASTLY_INCOMING_WEBHOOK_URL = 'system/full_page_cache/fastly/fastly_web_hooks/incoming_webhook_url';

    /**
     * XML path to enable Publish Key and URL Purge Events
     */
    const XML_FASTLY_PUBLISH_KEY_URL_PURGE_EVENTS
        = 'system/full_page_cache/fastly/fastly_web_hooks/publish_key_url_purge_events';

    /**
     * XML path to enable Publish Purge All/Clean All Items Events
     */
    const XML_FASTLY_PUBLISH_PURGE_ALL_EVENTS
        = 'system/full_page_cache/fastly/fastly_web_hooks/publish_purge_all_items_events';

    /**
     * XML path to enable Publish Purge All/Clean backtrace
     */
    const XML_FASTLY_PUBLISH_PURGE_ALL_TRACE
        = 'system/full_page_cache/fastly/fastly_web_hooks/publish_purge_all_trace';

    /**
     * XML path to enable Publish Config change events
     */
    const XML_FASTLY_PUBLISH_CONFIG_CHANGE_EVENTS
        = 'system/full_page_cache/fastly/fastly_web_hooks/publish_config_change_events';

    /**
     * XML path to enable Publish Config change events
     */
    const XML_FASTLY_WEBHOOK_MESSAGE_PREFIX
        = 'system/full_page_cache/fastly/fastly_web_hooks/webhook_message_prefix';

    /**
     * Check if Fastly is selected for Caching Application
     *
     * @return bool
     */
    public function isFastlyEnabled()
    {
        if ($this->getType() == Config::FASTLY) {
            return true;
        }

        return false;
    }

    /**
     * Return Fastly module version from core resource
     *
     * @return string
     */
    public function getFastlyVersion()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_MODULE_VERSION);
    }

    /**
     * Return Google Analytics CID
     *
     * @return string
     */
    public function getCID()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_GA_CID);
    }

    /**
     * Return Fastly API endpoint
     *
     * @return string
     */
    public function getApiEndpoint()
    {
        return self::FASTLY_API_ENDPOINT;
    }

    /**
     * Return Fastly service IP
     *
     * @return int
     */
    public function getServiceId()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_SERVICE_ID);
    }

    /**
     * Return Fastly API token
     *
     * @return int
     */
    public function getApiKey()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_API_KEY);
    }

    /**
     * Return page lifetime
     *
     * @return int
     */
    public function getTtl()
    {
        return (int)$this->_scopeConfig->getValue(self::XML_PAGECACHE_TTL);
    }

    /**
     * Return page lifetime
     *
     * @return int
     */
    public function getStaleTtl()
    {
        return (int)$this->_scopeConfig->getValue(self::XML_FASTLY_STALE_TTL);
    }

    /**
     * Return page lifetime
     *
     * @return int
     */
    public function getStaleErrorTtl()
    {
        return (int)$this->_scopeConfig->getValue(self::XML_FASTLY_STALE_ERROR_TTL);
    }

    /**
     * Return admin path timeout
     *
     * @return int
     */
    public function getAdminPathTimeout()
    {
        return (int)$this->_scopeConfig->getValue(self::XML_FASTLY_ADMIN_PATH_TIMEOUT);
    }

    public function getXMagentoTagsSize()
    {
        return (int)$this->_scopeConfig->getValue(self::XML_FASTLY_X_MAGENTO_TAGS_SIZE);
    }

    /**
     * Return Fastly ignored url parameters
     *
     * @return int
     */
    public function getIgnoredUrlParameters()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_IGNORED_URL_PARAMETERS);
    }

    /**
     * Return basic auth status
     *
     * @return int
     */
    public function getBasicAuthenticationStatus()
    {
        return (int)$this->_scopeConfig->getValue(self::FASTLY_BASIC_AUTH_ENABLE);
    }

    /**
     * Returns can purge catalog category.
     *
     * @return bool
     */
    public function canPurgeCatalogCategory()
    {
        return $this->_scopeConfig->isSetFlag(self::XML_FASTLY_PURGE_CATALOG_CATEGORY);
    }

    /**
     * Returns can purge catalog product.
     *
     * @return bool
     */
    public function canPurgeCatalogProduct()
    {
        return $this->_scopeConfig->isSetFlag(self::XML_FASTLY_PURGE_CATALOG_PRODUCT);
    }

    /**
     * Returns can purge CMS page.
     *
     * @return bool
     */
    public function canPurgeCmsPage()
    {
        return $this->_scopeConfig->isSetFlag(self::XML_FASTLY_PURGE_CMS_PAGE);
    }

    /**
     * Should we flush all or preserve static?
     *
     * @return bool
     */
    public function canPreserveStatic()
    {
        return $this->_scopeConfig->isSetFlag(self::XML_FASTLY_PRESERVE_STATIC);
    }

    /**
     * Returns can use soft purge
     *
     * @return bool
     */
    public function canUseSoftPurge()
    {
        return $this->_scopeConfig->isSetFlag(self::XML_FASTLY_SOFT_PURGE);
    }

    /**
     * Return is GeoIP enabled
     *
     * @return bool
     */
    public function isGeoIpEnabled()
    {
        return ($this->isEnabled() && $this->_scopeConfig->isSetFlag(self::XML_FASTLY_GEOIP_ENABLED));
    }

    /**
     * Return GeoIP action
     *
     * @return string
     */
    public function getGeoIpAction()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_GEOIP_ACTION);
    }

    /**
     * Return GeoIP redirect mapping
     *
     * @return array
     */
    public function getGeoIpRedirectMapping()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_GEOIP_COUNTRY_MAPPING);
    }

    /**
     * Determines should Image optimization be used
     *
     * @return bool
     */
    public function isImageOptimizationEnabled()
    {
        if ($this->isFastlyEnabled() !== true) {
            return false;
        }

        return $this->_scopeConfig->isSetFlag(self::XML_FASTLY_IMAGE_OPTIMIZATIONS);
    }

    /**
     * Determines should Image optimization pixel ratios be used
     *
     * @return bool
     */
    public function isImageOptimizationPixelRatioEnabled()
    {
        if ($this->isImageOptimizationEnabled() !== true) {
            return false;
        }

        return $this->_scopeConfig->isSetFlag(self::XML_FASTLY_IMAGE_OPTIMIZATIONS_PIXEL_RATIO);
    }

    /**
     * Return image optimization pixel ratios
     *
     * @return mixed
     */
    public function getImageOptimizationRatios()
    {
        return $this->_scopeConfig->getvalue(self::XML_FASTLY_IMAGE_OPTIMIZATIONS_RATIOS);
    }

    /**
     * Return blocked countries
     *
     * @return mixed
     */
    public function getBlockByCountry()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_BLOCK_BY_COUNTRY);
    }

    /**
     * Return blocked Acls
     *
     * @return mixed
     */
    public function getBlockByAcl()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_BLOCK_BY_ACL);
    }

    /**
     * Return are Webhooks enabled
     *
     * @return bool
     */
    public function areWebHooksEnabled()
    {
        return ($this->isEnabled() && $this->_scopeConfig->isSetFlag(self::XML_FASTLY_WEBHOOKS_ENABLED));
    }

    /**
     * Get Webhooks Endpoint URL
     * @return mixed
     */
    public function getIncomingWebhookURL()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_INCOMING_WEBHOOK_URL);
    }

    /**
     * Get Webhooks Username
     * @return mixed
     */
    public function getWebhookUsername()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_WEBHOOKS_USERNAME);
    }

    /**
     * Return is Publish Key and URL Purge Events enabled
     *
     * @return bool
     */
    public function canPublishKeyUrlChanges()
    {
        return ($this->isEnabled() && $this->_scopeConfig->isSetFlag(self::XML_FASTLY_PUBLISH_KEY_URL_PURGE_EVENTS));
    }

    /**
     * return is Publish Purge All/Clean All Items Events enabled
     *
     * @return bool
     */
    public function canPublishPurgeAllChanges()
    {
        return ($this->isEnabled() && $this->_scopeConfig->isSetFlag(self::XML_FASTLY_PUBLISH_PURGE_ALL_EVENTS));
    }

    /**
     * Is publishing backtrace on purgall allowed
     *
     * @return bool
     */
    public function canPublishDebugBacktrace()
    {
        return ($this->isEnabled() && $this->_scopeConfig->isSetFlag(self::XML_FASTLY_PUBLISH_PURGE_ALL_TRACE));
    }

    /**
     * return is Publish Config change events enabled
     *
     * @return bool
     */
    public function canPublishConfigChanges()
    {
        return ($this->isEnabled() && $this->_scopeConfig->isSetFlag(self::XML_FASTLY_PUBLISH_CONFIG_CHANGE_EVENTS));
    }

    /**
     * return Webhook message format
     *
     * @return mixed
     */
    public function getWebhookMessagePrefix()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_WEBHOOK_MESSAGE_PREFIX);
    }

    /**
     * return Webhook message format
     *
     * @return mixed
     */
    public function getLastCheckedIssuedVersion()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_LAST_CHECKED_ISSUED_VERSION);
    }

    /**
     * Get store ID for country.
     * @param $countryCode 2-digit country code
     * @return int|null
     */
    public function getGeoIpMappingForCountry($countryCode)
    {
        if ($mapping = $this->_scopeConfig->getValue(self::XML_FASTLY_GEOIP_COUNTRY_MAPPING)) {
            return $this->extractMapping($mapping, $countryCode);
        }
        return null;
    }

    /**
     * Filter country code mapping by priority
     * @param $mapping
     * @param $countryCode
     * @return int|null
     */
    private function extractMapping($mapping, $countryCode)
    {
        $final = null;
        $extractMapping = json_decode($mapping, true);
        if (!$extractMapping) {
            try {
                $extractMapping = unserialize($mapping); // @codingStandardsIgnoreLine
            } catch (\Exception $e) {
                $extractMapping = [];
            }
        }

        if (is_array($extractMapping)) {
            $countryId = 'country_id';
            $key = 'store_id';
            // check for direct match
            foreach ($extractMapping as $map) {
                if (is_array($map) &&
                    isset($map[$countryId]) &&
                    strtolower(str_replace(' ', '', $map[$countryId])) == strtolower($countryCode)) {
                    if (isset($map[$key])) {
                        return (int)$map[$key];
                    }
                } elseif (is_array($map) &&
                    isset($map[$countryId]) &&
                    $map[$countryId] == '*' &&
                    isset($map[$key]) &&
                    $final === null) {
                    // check for wildcard
                    $final = (int)$map[$key];
                }
            }
        }
        return $final;
    }
    
    /**
     * Return generated magento2_fastly_varnish.vcl configuration file
     *
     * @param string $vclTemplatePath
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getVclFile($vclTemplatePath) // @codingStandardsIgnoreLine - Unused parameter required due to compatibility with parent class
    {
        $moduleEtcPath = $this->reader->getModuleDir(Dir::MODULE_ETC_DIR, 'Fastly_Cdn');
        $configFilePath = $moduleEtcPath . '/' . $this->_scopeConfig->getValue(self::FASTLY_CONFIGURATION_PATH);
        $directoryRead = $this->readFactory->create($moduleEtcPath);
        $configFilePath = $directoryRead->getRelativePath($configFilePath);
        $data = $directoryRead->readFile($configFilePath);
        return strtr($data, $this->getReplacements());
    }

    public function getVclSnippets($path = '/vcl_snippets', $specificFile = null)
    {
        $snippetsData = [];

        $moduleEtcPath = $this->reader->getModuleDir(Dir::MODULE_ETC_DIR, 'Fastly_Cdn') . $path;
        $directoryRead = $this->readFactory->create($moduleEtcPath);
        if (!$specificFile) {
            $files = $directoryRead->read();

            if (is_array($files)) {
                foreach ($files as $file) {
                    if (substr($file, strpos($file, ".") + 1) !== 'vcl') {
                        continue;
                    }
                    $snippetFilePath = $moduleEtcPath . '/' . $file;
                    $snippetFilePath = $directoryRead->getRelativePath($snippetFilePath);
                    $type = explode('.', $file)[0];
                    $snippetsData[$type] = $directoryRead->readFile($snippetFilePath);
                }
            }
        } else {
            $snippetFilePath = $moduleEtcPath . '/' . $specificFile;
            $snippetFilePath = $directoryRead->getRelativePath($snippetFilePath);
            $type = explode('.', $specificFile)[0];
            $snippetsData[$type] = $directoryRead->readFile($snippetFilePath);
        }

        return $snippetsData;
    }

    /**
     * Prepare data for VCL config
     *
     * @return array
     */
    private function getReplacements()
    {
        return [
            '### {{ design_exceptions_code }} ###' => $this->getDesignExceptions()
        ];
    }

    /**
     * Get regexs for design exceptions
     * Different browser user-agents may use different themes
     * Varnish supports regex with internal modifiers only so
     * we have to convert "/pattern/iU" into "(?Ui)pattern"
     *
     * @return string
     */
    private function getDesignExceptions()
    {
        $result = '';
        $tpl = "        %s (req.http.user-agent ~ \"%s\") {\n" . "            set req.hash += \"%s\";\n" . "        }";

        $expressions = $this->_scopeConfig->getValue(
            self::XML_VARNISH_PAGECACHE_DESIGN_THEME_REGEX,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($expressions) {
            try {
                $expressions = unserialize($expressions); // @codingStandardsIgnoreLine - used for conversion of old Magento format to json_decode
            } catch (\Exception $e) {
                $expressions = [];
            }
            $rules = array_values($expressions);
            foreach ($rules as $i => $rule) {
                if (preg_match('/^[\W]{1}(.*)[\W]{1}(\w+)?$/', $rule['regexp'], $matches)) {
                    if (!empty($matches[2])) {
                        $pattern = sprintf("(?%s)%s", $matches[2], $matches[1]);
                    } else {
                        $pattern = $matches[1];
                    }
                    $if = $i == 0 ? 'if' : ' elsif';
                    $result .= sprintf($tpl, $if, $pattern, $rule['value']);
                }
            }
        }

        if (!empty($result)) {
            $result = 'if (req.url ~ "^/(pub/)?(media|static)/.*") {' . "\n" . $result . "\n    }";
        }

        return $result;
    }
}
