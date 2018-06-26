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
namespace Fastly\Cdn\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class CacheTags
 *
 * @package Fastly\Cdn\Helper
 */
class CacheTags extends AbstractHelper
{
    /**
     * Replaces long Magento Cache tags with a shorter version
     *
     * @param string
     * @return string
     */
    public function convertCacheTags($tags)
    {
        $fastlyTags = [
            // 2.1.*
            'catalog_product_' => 'p',
            'catalog_category_' => 'c',
            'cms_page' => 'cpg',
            'cms_block' => 'cb',

            // 2.2.*
            'cat_p_' => 'p',
            'cat_c_' => 'c',
            'cms_p' => 'cpg',
            'cms_b' => 'cb',

            // Other
            'brands_brand_' => 'b'
        ];

        return str_replace(array_keys($fastlyTags), $fastlyTags, $tags);
    }
}
