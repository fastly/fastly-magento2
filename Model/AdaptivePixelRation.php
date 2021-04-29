<?php

namespace Fastly\Cdn\Model;

/**
 * Class AdaptivePixelRation
 * @package Fastly\Cdn\Model
 */
class AdaptivePixelRation
{
    /**
     * @param $imageUrl
     * @param array $pixelRatios
     * @return array
     */
    public function generateSrcSet($imageUrl, array $pixelRatios): array
    {
        $srcSets = [];
        $glue = !strpos($imageUrl, '?') ? '&' : '?';

        foreach ($pixelRatios as $pr) {
            if (!$pr)
                continue;

            $ratio = 'dpr=' . $pr . ' ' . $pr . 'x';
            $srcSets[$pr] = $imageUrl . $glue . $ratio;
        }

        return $srcSets;
    }
}
