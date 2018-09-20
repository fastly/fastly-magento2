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
use Fastly\Cdn\Model\Config;

/**
 * Class ResponsePlugin
 *
 * @package Fastly\Cdn\Model
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
        $this->config       = $config;
        $this->cacheTags    = $cacheTags;
    }

    /**
     * Alter the X-Magento-Tags header
     *
     * @param Http $subject
     * @param callable $proceed
     * @param array ...$args
     * @return mixed
     */
    public function aroundSetHeader(Http $subject, callable $proceed, ...$args) // @codingStandardsIgnoreLine - unused parameter
    {
        // Is Fastly cache enabled?
        if ($this->config->getType() !== Config::FASTLY) {
            return $proceed(...$args);
        }

        // Is current header X-Magento-Tags
        if (isset($args[0]) == true && $args[0] !== 'X-Magento-Tags') {
            return $proceed(...$args);
        }

        // Make the necessary adjustment
        $args[1] = $this->cacheTags->convertCacheTags(str_replace(',', ' ', $args[1]));
        $tagsSize = $this->config->getXMagentoTagsSize();
        if (strlen($args[1]) > $tagsSize) {
            $trimmedArgs = substr($args[1], 0, $tagsSize);
            $args[1] = substr($trimmedArgs, 0, strrpos($trimmedArgs, ' ', -1));
        }

        // Proceed
        return $proceed(...$args);
    }
}
