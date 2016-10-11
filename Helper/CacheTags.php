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
        $fastlyTags = array(
            'catalog_product_' => 'p',
            'catalog_category_' => 'c',
            'cms_page' => 'cpg',
            'cms_block' => 'cb',
            'brands_brand_' => 'b'
        );

        return str_replace(array_keys($fastlyTags), $fastlyTags, $tags);
    }
}