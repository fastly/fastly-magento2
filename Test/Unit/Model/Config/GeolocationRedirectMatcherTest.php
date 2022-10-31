<?php

namespace Fastly\Cdn\Test\Unit\Model\Config;

use Fastly\Cdn\Model\Config\GeolocationRedirectMatcher;
use PHPUnit\Framework\TestCase;

class GeolocationRedirectMatcherTest extends TestCase
{
    private GeolocationRedirectMatcher $matcher;

    public function setUp(): void
    {
        $this->matcher = new GeolocationRedirectMatcher();
    }

    public function testWithOldEntries(): void
    {
        $map = [
            [
                'country_id' => 'HR',
                'store_id' => '5'
            ],
            [
                'country_id' => 'IE',
                'origin_website_id' => '',
                'store_id' => '6'
            ],
        ];

        $this->assertEquals(5, $this->matcher->execute($map, 'HR', 42));
        $this->assertEquals(6, $this->matcher->execute($map, 'IE', 24));
    }

    public function testWithWildcardCountry(): void
    {
        $map = [
            [
                'country_id' => '*',
                'store_id' => '6'
            ],
        ];

        $this->assertEquals(6, $this->matcher->execute($map, 'US', 42));
    }

    public function testWithSpecificWebsite(): void
    {
        $map = [
            [
                'country_id' => 'US',
                'origin_website_id' => '5',
                'store_id' => '123'
            ],
            [
                'country_id' => 'US',
                'origin_website_id' => '2',
                'store_id' => '321'
            ],
        ];

        $this->assertEquals(321, $this->matcher->execute($map, 'US', 2));
        $this->assertEquals(123, $this->matcher->execute($map, 'US', 5));
    }

    public function testWithVariousSpecificity(): void
    {
        $map = [
            [
                'country_id' => '*',
                'origin_website_id' => '',
                'store_id' => '111'
            ],
            [
                'country_id' => 'US',
                'origin_website_id' => '',
                'store_id' => '222'
            ],
            [
                'country_id' => 'US',
                'origin_website_id' => '33',
                'store_id' => '333'
            ],
        ];

        $this->assertEquals(111, $this->matcher->execute($map, 'DE', 123));
        $this->assertEquals(222, $this->matcher->execute($map, 'US', 123));
        $this->assertEquals(333, $this->matcher->execute($map, 'US', 33));
    }

    public function testWithVariousCountrySpecificity(): void
    {
        $map = [
            [
                'country_id' => '*',
                'origin_website_id' => '1',
                'store_id' => '1'
            ],
            [
                'country_id' => 'US',
                'origin_website_id' => '1',
                'store_id' => '2'
            ],
        ];

        $this->assertEquals(1, $this->matcher->execute($map, 'DE', 1));
        $this->assertEquals(2, $this->matcher->execute($map, 'US', 1));
    }

    public function testDifferentWebsitesForSameCountry(): void
    {
        $map = [
            [
                'country_id' => 'JP',
                'origin_website_id' => '42',
                'store_id' => '1'
            ],
            [
                'country_id' => 'JP',
                'origin_website_id' => '1',
                'store_id' => '2'
            ],
            [
                'country_id' => 'JP',
                'origin_website_id' => '5',
                'store_id' => '3'
            ],
        ];

        $this->assertEquals(1, $this->matcher->execute($map, 'JP', 42));
        $this->assertEquals(2, $this->matcher->execute($map, 'JP', 1));
        $this->assertEquals(3, $this->matcher->execute($map, 'JP', 5));
    }

    public function testMultistoreConfiguration(): void
    {
        $map = [
            [
                'country_id' => 'US',
                'origin_website_id' => '1',
                'store_id' => '1'
            ],
            [
                'country_id' => 'DE',
                'origin_website_id' => '',
                'store_id' => '2'
            ],
            [
                'country_id' => 'DE',
                'origin_website_id' => '1',
                'store_id' => '3'
            ],
        ];

        $this->assertEquals(1, $this->matcher->execute($map, 'US', 1));
        $this->assertEquals(2, $this->matcher->execute($map, 'DE', 2));
        $this->assertEquals(3, $this->matcher->execute($map, 'DE', 1));
    }

    public function testBackwardCompatibility()
    {
        $map = [
            [
                'country_id' => 'FR',
                'store_id' => '3'
            ],
            [
                'country_id' => 'US',
                'store_id' => '1'
            ],
            [
                'country_id' => 'GB',
                'store_id' => '2'
            ],
            [
                'country_id' => '*',
                'store_id' => '2'
            ],
        ];

        $this->assertEquals(1, $this->matcher->execute($map, 'US', 1));
        $this->assertEquals(2, $this->matcher->execute($map, 'NZ', 1));
        $this->assertEquals(3, $this->matcher->execute($map, 'FR', 1));
        $this->assertEquals(2, $this->matcher->execute($map, 'GB', 1));
    }

    public function testComplexOverrides()
    {
        $map = [
            ['country_id' => 'US', 'origin_website_id' => '', 'store_id' => 1],
            ['country_id' => 'US', 'origin_website_id' => 2,  'store_id' => 4],
            ['country_id' => 'UK', 'origin_website_id' => '', 'store_id' => 2],
            ['country_id' => 'UK', 'origin_website_id' => 2,  'store_id' => 5],
            ['country_id' => '*',  'origin_website_id' => 1,  'store_id' => 3],
            ['country_id' => '*',  'origin_website_id' => 2,  'store_id' => 6],
        ];

        $this->assertEquals(4, $this->matcher->execute($map, 'US', 2));
        $this->assertEquals(6, $this->matcher->execute($map, 'JP', 2));
        $this->assertEquals(2, $this->matcher->execute($map, 'UK', 1));
        $this->assertEquals(6, $this->matcher->execute($map, 'NZ', 2));
    }
}
