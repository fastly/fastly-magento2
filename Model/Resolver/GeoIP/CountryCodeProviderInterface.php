<?php

declare(strict_types=1);

namespace Fastly\Cdn\Model\Resolver\GeoIP;


interface CountryCodeProviderInterface
{

    /**
     * @return string|null
     */
    public function getCountryCode(): ?string;
}
