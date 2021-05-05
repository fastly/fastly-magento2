<?php

namespace Fastly\Cdn\Model;

/**
 * Class AdaptivePixelRatio
 * @package Fastly\Cdn\Model
 */
class AdaptivePixelRatio
{
    /**
     * @param $imageUrl
     * @param array $pixelRatios
     * @return array
     */
    public function generateSrcSet($imageUrl, array $pixelRatios): array
    {
        $srcSets = [];
        $glue = strpos($imageUrl, '?') !== false ? '&' : '?';
        foreach ($pixelRatios as $pr) {
            if (!$pr)
                continue;

            $ratio = 'dpr=' . $pr . ' ' . $pr . 'x';
            $srcSets[$pr] = $imageUrl . $glue . $ratio;
        }

        return $srcSets;
    }
}
