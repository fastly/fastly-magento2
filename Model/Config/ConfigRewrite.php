<?php
namespace Fastly\Cdn\Model\Config;

/**
 * Used for sending purge after disabling Fastly as caching service
 *
 * @author Inchoo
 */
class ConfigRewrite
{
    protected $_purge = false;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig = null;

    /**
     * @var \Fastly\Cdn\Model\Api
     */
    protected $api;

    /**
     * ConfigRewrite constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Fastly\Cdn\Model\PurgeCache $api
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Fastly\Cdn\Model\Api $api
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_api = $api;
    }

    /**
     * Trigger purge if set
     * @param \Magento\Config\Model\Config $subject
     */
    public function afterSave(\Magento\Config\Model\Config $subject)
    {
        if($this->_purge) {
            $this->_api->cleanBySurrogateKey(['text']);
        }
    }

    /**
     * Set flag for purging if Fastly is switched off
     * @param \Magento\Config\Model\Config $subject
     */
    public function beforeSave(\Magento\Config\Model\Config $subject)
    {
        $data = $subject->getData();
        if(!empty($data['groups']['full_page_cache']['fields']['caching_application']['value'])) {
            $currentCacheConfig = $data['groups']['full_page_cache']['fields']['caching_application']['value'];
            $oldCacheConfig = $this->_scopeConfig->getValue(\Magento\PageCache\Model\Config::XML_PAGECACHE_TYPE);

            if($oldCacheConfig == \Fastly\Cdn\Model\Config::FASTLY && $currentCacheConfig != $oldCacheConfig) {
                $this->_purge = true;
            }
        }
    }
}