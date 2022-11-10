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

use Fastly\Cdn\Helper\CacheTags;
use Magento\Framework\App\Response\Http;

/**
 * Class ResponsePlugin for replacing X-Magento-Tags
 *
 */
class ResponsePlugin
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var CacheTags
     */
    private $cacheTags;

    /**
     * ResponsePlugin constructor.
     *
     * @param Config $config
     * @param CacheTags $cacheTags
     */
    public function __construct(
        Config $config,
        CacheTags $cacheTags
    ) {
        $this->config = $config;
        $this->cacheTags = $cacheTags;
    }

    /**
     * Alter the X-Magento-Tags header
     *
     * @param Http $subject
     * @param callable $proceed
     * @param string $name
     * @param string $value
     * @param bool $replace
     * @return mixed
     */
    public function aroundSetHeader(Http $subject, callable $proceed, $name, $value, $replace = false) // @codingStandardsIgnoreLine - unused parameter
    {
        // Is Fastly cache enabled?
        if ((int)$this->config->getType() !== Config::FASTLY) {
            return $proceed($name, $value, $replace);
        }

        // Is current header X-Magento-Tags
        if ($name !== 'X-Magento-Tags') {
            return $proceed($name, $value, $replace);
        }
        $value = (string)$value;

        // Make the necessary adjustment
        $value = $this->cacheTags->convertCacheTags(str_replace(',', ' ', $value));
        $tagsSize = $this->config->getXMagentoTagsSize();
        if (strlen($value) > $tagsSize) {
            $trimmedArgs = substr($value, 0, $tagsSize);
            $value = substr($trimmedArgs, 0, strrpos($trimmedArgs, ' ', -1));
        }

        return $proceed($name, $value, $replace);
    }
}
