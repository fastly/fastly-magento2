<?php

namespace Fastly\Cdn\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

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
