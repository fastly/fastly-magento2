<?php

namespace Fastly\Cdn\Model\Config;

class GeolocationRedirectMatcher
{
    /**
     * Find a matching destination store ID.
     *
     * @param array $map
     * @param string $countryCode
     * @param int $websiteId
     * @return int|null
     */
    public function execute(array $map, string $countryCode, int $websiteId): ?int
    {
        $match = null;
        $bestMatchScore = 0;

        foreach ($map as $mapEntry) {
            if (!is_array($mapEntry) || !isset($mapEntry['country_id'], $mapEntry['store_id'])) {
                // Map entry invalid.
                continue;
            }

            $mapCountry = strtolower(str_replace(' ', '', $mapEntry['country_id']));
            $matchesCountry = $mapCountry === '*' || $mapCountry === strtolower($countryCode);
            if (!$matchesCountry) {
                // Request country does not match country filter.
                continue;
            }

            $mapOriginWebsite = isset($mapEntry['origin_website_id']) && is_numeric($mapEntry['origin_website_id']) ?
                $mapEntry['origin_website_id'] :
                'any'; // Default to 'any' if origin website ID is not set.
            $matchesOriginWebsite = $mapOriginWebsite === 'any' || ((int)$mapOriginWebsite === $websiteId);
            if (!$matchesOriginWebsite) {
                // Origin website filter is set, and current website ID does not satisfy it.
                continue;
            }

            $matchScore = 1 + (int)($mapCountry !== '*') + (int)($mapOriginWebsite !== 'any');
            if ($matchScore > $bestMatchScore) {
                $match = (int)$mapEntry['store_id'];
                $bestMatchScore = $matchScore;
            }
        }

        return $match;
    }
}
