<?php

namespace Fastly\Cdn\Model;

use Magento\Framework\App\ProductMetadataInterface;

/**
 * Class AdaptivePixelRation
 * @package Fastly\Cdn\Model
 */
class AdaptivePixelRation
{

    /**
     * @param $imageUrl
     * @param array $pixelRatios
     * @return string
     */
    public function generateSrcSet($imageUrl, array $pixelRatios): string
    {
        $srcSet = [];
        $glue = !strpos($imageUrl, '?') ? '&' : '?';

        foreach ($pixelRatios as $pr) {
            $ratio = 'dpr=' . $pr . ' ' . $pr . 'x';
            $srcSet[] = $imageUrl . $glue . $ratio;
        }

        return implode(',', $srcSet);
    }
}
