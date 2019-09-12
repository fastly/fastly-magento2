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
 * @package Fastly\Cdn\Model
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
     * Edge module prefix used for naming edge module snippets
     */
    const FASTLY_MODLY_MODULE = 'edgemodule';

    /**
     * File name used in the export functionality
     */
    const EXPORT_FILE_NAME = 'fastly_config.json';

    /**
     * Magento Error Page Response Object Name
     */
    const ERROR_PAGE_RESPONSE_OBJECT = self::FASTLY_MAGENTO_MODULE . '_error_page_response_object';

    /**
     * WAF Page Response Object Name
     */
    const WAF_PAGE_RESPONSE_OBJECT = 'WAF_Response';

    /**
     * GeoIP action "dialog"
     */
    const GEOIP_ACTION_DIALOG = 'dialog';

    /**
     * GeoIP action "redirect"
     */
    const GEOIP_ACTION_REDIRECT = 'redirect';

    /**
     * Blocking snippets directory path
     */
    const VCL_BLOCKING_PATH = '/vcl_snippets_blocking';

    /**
     * VCL blocking snippet
     */
    const VCL_BLOCKING_SNIPPET = 'recv.vcl';

    /**
     * Blocking setting name
     */
    const BLOCKING_SETTING_NAME = self::FASTLY_MAGENTO_MODULE . '_blocking_recv';

    /**
     * Rate Limiting snippets directory path
     */
    const VCL_RATE_LIMITING_PATH = '/vcl_snippets_rate_limiting';

    /**
     * Rate limiting snippet
     */
    const VCL_RATE_LIMITING_SNIPPET = 'recv.vcl';

    /**
     * Rate Limiting setting name
     */
    const RATE_LIMITING_SETTING_NAME = self::FASTLY_MAGENTO_MODULE . '_rate_limiting';

    /**
     * WAF snippets directory path
     */
    const VCL_WAF_PATH = '/vcl_snippets_waf';

    /**
     * VCL WAF snippet
     */
    const VCL_WAF_ALLOWLIST_SNIPPET = 'recv.vcl';

    /**
     * WAF setting name
     */
    const WAF_SETTING_NAME = self::FASTLY_MAGENTO_MODULE . '_waf_recv';

    /**
     * Authentication snippets directory path
     */
    const VCL_AUTH_SNIPPET_PATH = '/vcl_snippets_basic_auth';

    /**
     * Maintenance snippets directory path
     */
    const VCL_MAINT_SNIPPET_PATH = '/vcl_snippets_maintenance';

    /**
     * Authentication dictionary name
     */
    const AUTH_DICTIONARY_NAME = self::FASTLY_MAGENTO_MODULE . '_basic_auth';

    /**
     * Image optimization setting name
     */
    const IMAGE_SETTING_NAME = self::FASTLY_MAGENTO_MODULE . '_image_optimization_recv';

    /**
     * Force TLS snippet path
     */
    const FORCE_TLS_PATH = '/vcl_snippets_force_tls';

    /**
     * Force TLS setting name
     */
    const FORCE_TLS_SETTING_NAME = self::FASTLY_MAGENTO_MODULE . '_force_tls_recv';

    /**
     * Configure Dictionary name
     */
    const CONFIG_DICTIONARY_NAME = self::FASTLY_MAGENTO_MODULE . '_config';

    /**
     * Maintenance Allowlist name
     */
    const MAINT_ACL_NAME = 'maint_allowlist';

    /**
     * Config Dictionary key
     */
    const CONFIG_DICTIONARY_KEY = 'allow_super_users_during_maint';

    /**
     * Custom snippet path
     */
    const CUSTOM_SNIPPET_PATH = 'vcl_snippets_custom/';

    /**
     * Image optimization condition name
     */
    const IO_CONDITION_NAME = 'fastly-image-optimizer-condition';

    /**
     * Image optimization header name
     */
    const IO_HEADER_NAME = 'fastly-image-optimizer-header';

    /**
     * Image optimization snippet path
     */
    const IO_VCL_SNIPPET_PATH = '/vcl_snippets_image_optimizations';

    /**
     * Error page snippet path
     */
    const VCL_ERROR_SNIPPET_PATH = '/vcl_snippets_error_page';

    /**
     * Error page snippet
     */
    const VCL_ERROR_SNIPPET = 'deliver.vcl';

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
     * Max first byte timeout value
     */
    const XML_FASTLY_MAX_FIRST_BYTE_TIMEOUT = 600;

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
     * XML path to Rate Limiting paths
     */
    const XML_FASTLY_RATE_LIMITING_PATHS
        = 'system/full_page_cache/fastly/fastly_rate_limiting_settings/rate_limiting_paths';

    /**
     * XML path to Rate Limiting limit
     */
    const XML_FASTLY_RATE_LIMITING_LIMIT
        = 'system/full_page_cache/fastly/fastly_rate_limiting_settings/rate_limiting_limit';

    /**
     * XML path to Rate Limiting TTL
     */
    const XML_FASTLY_RATE_LIMITING_TTL
        = 'system/full_page_cache/fastly/fastly_rate_limiting_settings/rate_limiting_ttl';

    /**
     * XML path to image optimizations flag
     */
    const XML_FASTLY_IMAGE_OPTIMIZATIONS
        = 'system/full_page_cache/fastly/fastly_image_optimization_configuration/image_optimizations';

    /**
     * XML path to image optimization force lossy flag
     */
    const XML_FASTLY_FORCE_LOSSY
        = 'system/full_page_cache/fastly/fastly_image_optimization_configuration/image_optimization_force_lossy';

    /**
     * XML path to image optimization bg color flag
     */
    const XML_FASTLY_IMAGE_OPTIMIZATION_BG_COLOR
        = 'system/full_page_cache/fastly/fastly_image_optimization_configuration/image_optimization_bg_color';

    /**
     * XML path to image optimization image quality value
     */
    const XML_FASTLY_IMAGE_OPTIMIZATION_IMAGE_QUALITY
        = 'system/full_page_cache/fastly/fastly_image_optimization_configuration/image_optimization_image_quality';

    /**
     * XML path to image optimization canvas flag
     */
    const XML_FASTLY_IMAGE_OPTIMIZATION_CANVAS
        = 'system/full_page_cache/fastly/fastly_image_optimization_configuration/image_optimization_canvas';

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
     * XML path to the Fastly Blocking Type flag
     */
    const XML_FASTLY_BLOCKING_TYPE = 'system/full_page_cache/fastly/fastly_blocking/blocking_type';

    /**
     * XML path to Fastly list of WAF allowed Acls
     */
    const XML_FASTLY_WAF_ALLOW_BY_ACL =
        'system/full_page_cache/fastly/fastly_web_application_firewall/waf_allow_by_acl';

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
     * XML path to enable Publish Purge Events
     */
    const XML_FASTLY_PUBLISH_PURGE_EVENTS
        = 'system/full_page_cache/fastly/fastly_web_hooks/publish_purge_events';

    /**
     * XML path to enable Publish Purge All/Clean backtrace
     */
    const XML_FASTLY_PUBLISH_PURGE_ALL_TRACE
        = 'system/full_page_cache/fastly/fastly_web_hooks/publish_purge_all_trace';

    /**
     * XML path to enable Publish Purge By Key backtrace
     */
    const XML_FASTLY_PUBLISH_PURGE_BY_KEY_TRACE
        = 'system/full_page_cache/fastly/fastly_web_hooks/publish_purge_by_key_trace';

    /**
     * XML path to enable Publish Generic Purge
     */
    const XML_FASTLY_PUBLISH_PURGE_TRACE
        = 'system/full_page_cache/fastly/fastly_web_hooks/publish_purge_trace';

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
     * XML path to enable Rate Limiting
     */
    const XML_FASTLY_RATE_LIMITING_ENABLE
        = 'system/full_page_cache/fastly/fastly_rate_limiting_settings/enable_rate_limiting';

    /**
     * XML path to enable Crawler Protection
     */
    const XML_FASTLY_CRAWLER_PROTECTION_ENABLE
        = 'system/full_page_cache/fastly/fastly_rate_limiting_settings/crawler_protection/enable_crawler_protection';

    /**
     * XML path to Crawler Protection Rate Limiting limit
     */
    const XML_FASTLY_CRAWLER_RATE_LIMITING_LIMIT
        = 'system/full_page_cache/fastly/fastly_rate_limiting_settings/crawler_protection/crawler_rate_limiting_limit';

    /**
     * XML path to Crawler Protection Rate Limiting TTL
     */
    const XML_FASTLY_CRAWLER_RATE_LIMITING_TTL
        = 'system/full_page_cache/fastly/fastly_rate_limiting_settings/crawler_protection/crawler_rate_limiting_ttl';

    /**
     * XML path to Exempt Good Bots flag
     */
    const XML_FASTLY_EXEMPT_GOOD_BOTS
        = 'system/full_page_cache/fastly/fastly_rate_limiting_settings/crawler_protection/exempt_good_bots';

    /**
     * Request Header for VCL comparison
     */
    const REQUEST_HEADER = 'Fastly-Magento-VCL-Uploaded';

    /**
     * Fastly module name
     */
    const FASTLY_MODULE_NAME = 'Fastly_Cdn';

    /**
     * core_config path for versions that has dismissed warning for outdated vcl
     */
    const VERSIONS_WITH_DISMISSED_WARNING
        = 'Fastly/Cdn/versions_with_dismissed_vcl_warning';

    /**
     * core_config path for last update VCL to Fastly time
     */
    const UPDATED_VCL_FLAG = 'Fastly/Cdn/updated_VCL_to_Fastly_flag';

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
     * Checks if the image optimization force lossy option is enabled
     *
     * @return mixed
     */
    public function isForceLossyEnabled()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_FORCE_LOSSY);
    }

    /**
     * Checks if the image optimization bg color option is enabled
     *
     * @return mixed
     */
    public function isImageOptimizationBgColorEnabled()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_IMAGE_OPTIMIZATION_BG_COLOR);
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

    public function getWafAllowByAcl()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_WAF_ALLOW_BY_ACL);
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
     *
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
     * Is publishing purge changes allowed
     *
     * @return bool
     */
    public function canPublishPurgeChanges()
    {
        return ($this->isEnabled() && $this->_scopeConfig->isSetFlag(self::XML_FASTLY_PUBLISH_PURGE_EVENTS));
    }

    /**
     * Is publishing backtrace on purge all allowed
     *
     * @return bool
     */
    public function canPublishPurgeAllDebugBacktrace()
    {
        return ($this->isEnabled() && $this->_scopeConfig->isSetFlag(self::XML_FASTLY_PUBLISH_PURGE_ALL_TRACE));
    }

    /**
     * Is publishing backtrace on purge by key allowed
     *
     * @return bool
     */
    public function canPublishPurgeByKeyDebugBacktrace()
    {
        return ($this->isEnabled() && $this->_scopeConfig->isSetFlag(self::XML_FASTLY_PUBLISH_PURGE_BY_KEY_TRACE));
    }

    /**
     * Is publishing backtrace on generic purge allowed
     *
     * @return bool
     */
    public function canPublishPurgeDebugBacktrace()
    {
        return ($this->isEnabled() && $this->_scopeConfig->isSetFlag(self::XML_FASTLY_PUBLISH_PURGE_TRACE));
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
     * return Rate Limiting status
     *
     * @return mixed
     */
    public function isRateLimitingEnabled()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_RATE_LIMITING_ENABLE);
    }

    /**
     * return Rate Limiting limit
     *
     * @return mixed
     */
    public function getRateLimitingLimit()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_RATE_LIMITING_LIMIT);
    }

    /**
     * return Rate Limiting TTL
     *
     * @return mixed
     */
    public function getRateLimitingTtl()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_RATE_LIMITING_TTL);
    }

    /**
     * return Crawler Protection status
     *
     * @return mixed
     */
    public function isCrawlerProtectionEnabled()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_CRAWLER_PROTECTION_ENABLE);
    }

    /**
     * return Crawler Rate Limiting limit
     *
     * @return mixed
     */
    public function getCrawlerRateLimitingLimit()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_CRAWLER_RATE_LIMITING_LIMIT);
    }

    /**
     * return Crawler Rate Limiting TTL
     *
     * @return mixed
     */
    public function getCrawlerRateLimitingTtl()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_CRAWLER_RATE_LIMITING_TTL);
    }

    /**
     * Check if exempt good bots is enabled
     *
     * @return mixed
     */
    public function isExemptGoodBotsEnabled()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_EXEMPT_GOOD_BOTS);
    }

    /**
     * Get store ID for country.
     *
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
     *
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
     * @return array|mixed|null
     */
    public function getRateLimitPaths()
    {
        return $this->_scopeConfig->getValue(self::XML_FASTLY_RATE_LIMITING_PATHS);
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

    /**
     * Returns VCL snippet data
     *
     * @param string $path
     * @param null $specificFile
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
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

    public function getCustomSnippets($path, $specificFile = null)
    {
        $snippetsData = [];
        try {
            $directoryRead = $this->readFactory->create($path);
            if (!$specificFile) {
                $files = $directoryRead->read();

                if (is_array($files)) {
                    foreach ($files as $file) {
                        if (substr($file, strpos($file, ".") + 1) !== 'vcl') {
                            continue;
                        }
                        $snippetFilePath = $path . '/' . $file;
                        $snippetFilePath = $directoryRead->getRelativePath($snippetFilePath);
                        $type = explode('.', $file)[0];
                        $snippetsData[$type] = $directoryRead->readFile($snippetFilePath);
                    }
                }
            } else {
                $snippetFilePath = $path . '/' . $specificFile;
                $snippetFilePath = $directoryRead->getRelativePath($snippetFilePath);
                $type = explode('.', $specificFile)[0];
                $snippetsData[$type] = $directoryRead->readFile($snippetFilePath);
            }
            return $snippetsData;
        } catch (\Exception $e) {
            return $snippetsData;
        }
    }

    public function getFastlyEdgeModules($path = '/fastly_edge_modules', $specificFile = null)
    {
        $moduleData = [];
        try {
            $moduleEtcPath = $this->reader->getModuleDir(Dir::MODULE_ETC_DIR, 'Fastly_Cdn') . $path;
            $directoryRead = $this->readFactory->create($moduleEtcPath);
            if (!$specificFile) {
                $files = $directoryRead->read();

                if (is_array($files)) {
                    foreach ($files as $file) {
                        if (substr($file, strpos($file, ".") + 1) !== 'json') {
                            continue;
                        }
                        $fastlyModuleFilePath = $moduleEtcPath . '/' . $file;
                        $fastlyModuleFilePath = $directoryRead->getRelativePath($fastlyModuleFilePath);
                        $type = explode('.', $file)[0];
                        $moduleData[$type] = $directoryRead->readFile($fastlyModuleFilePath);
                    }
                }
            } else {
                $fastlyModuleFilePath = $moduleEtcPath . '/' . $specificFile;
                $fastlyModuleFilePath = $directoryRead->getRelativePath($fastlyModuleFilePath);
                $type = explode('.', $specificFile)[0];
                $moduleData[$type] = $directoryRead->readFile($fastlyModuleFilePath);
            }
            return $moduleData;
        } catch (\Exception $e) {
            return $moduleData;
        }
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

    /**
     * Validate custom snippet data
     *
     * @param $name
     * @param $type
     * @param $priority
     * @return array
     */
    public function validateCustomSnippet($name, $type, $priority)
    {
        $snippetName = str_replace(' ', '', $name);
        $types = ['init', 'recv', 'hit', 'miss', 'pass', 'fetch', 'error', 'deliver', 'log', 'hash', 'none'];

        $inArray = in_array($type, $types);
        $isNumeric = is_numeric($priority);
        $isAlphanumeric = preg_match('/^[\w]+$/', $snippetName);
        $error = null;

        if (!$inArray) {
            $error = 'Type value is not recognised.';
            return [
                'snippet_name' => null,
                'error' => $error
            ];
        }
        if (!$isNumeric) {
            $error = 'Please make sure that the priority value is a number.';
            return [
                'snippet_name' => null,
                'error' => $error
            ];
        }
        if (!$isAlphanumeric) {
            $error = 'Please make sure that the name value contains only alphanumeric characters.';
            return [
                'snippet_name' => null,
                'error' => $error
            ];
        }
        return [
            'snippet_name' => $snippetName,
            'error' => $error
        ];
    }

    /**
     * Process blocked items depending on blocking type
     *
     * @param $strippedBlockedItems
     * @param null $blockingType
     * @return string
     */
    public function processBlockedItems($strippedBlockedItems, $blockingType = null)
    {
        if (empty($blockingType)) {
            $blockingType = $this->_scopeConfig->getValue(self::XML_FASTLY_BLOCKING_TYPE);
        }
        if ($blockingType == '1') {
            $strippedBlockedItems = '!(' . $strippedBlockedItems . ')';
        }
        return $strippedBlockedItems;
    }
}
