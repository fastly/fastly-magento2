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
            'catalog_product' => 'p',
            'catalog_category' => 'c',
            'cms_page' => 'cpg'
        );

        return str_replace(array_keys($fastlyTags), $fastlyTags, $tags);
    }
}