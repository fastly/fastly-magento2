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
namespace Fastly\Cdn\Test\Unit\Model\PageCache;

use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Layout\LayoutPlugin;
use Fastly\Cdn\Model\PageCache\ConfigPlugin;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigPluginTest
 *
 * @package Fastly\Cdn\Test\Unit\Model\PageCache
 */
class ConfigPluginTest extends TestCase
{
     /**
      * @var LayoutPlugin
      */
     protected $model;

    public function setUp(): void
     {
         $scopeConfigMock = $this->getMockBuilder('\Magento\Framework\App\Config\ScopeConfigInterface')->getMock();
         $this->model = new ConfigPlugin(
             $scopeConfigMock
         );
     }

    /**
     * @param $isFastlyConfig
     * @param $result
     * @param $expectedOutput
     */
     #[DataProvider('afterGetTypeDataProvider')]
     public function testAfterGetType($isFastlyConfig, $result, $expectedOutput)
     {
         if ($isFastlyConfig) {
             $config = $this->getMockBuilder('Fastly\Cdn\Model\Config')->disableOriginalConstructor()->getMock();
         } else {
             $config = $this->getMockBuilder('Magento\PageCache\Model\Config')->disableOriginalConstructor()->getMock();
         }
         $output = $this->model->afterGetType($config, $result);
         $this->assertSame($expectedOutput, $output);
     }

    /**
     * @return array[]
     */
    public static function afterGetTypeDataProvider(): array
    {

        return [
            'Config: Fastly, Cache Type: Fastly, Expected: Fastly' => [true, Config::FASTLY, Config::FASTLY],
            'Config: Fastly, Cache Type: Varnish, Expected: Varnish' => [true, Config::VARNISH, Config::VARNISH],
            'Config: Fastly, Cache Type: Builtin, Expected: Builtin' => [true, Config::BUILT_IN, Config::BUILT_IN],
            'Config: PageCache, Cache Type: Fastly, Expected: Varnish' => [false, Config::FASTLY, Config::VARNISH],
            'Config: PageCache, Cache Type: Varnish, Expected: Varnish' => [false, Config::VARNISH, Config::VARNISH],
            'Config: PageCache, Cache Type: Builtin, Expected: Builtin' => [false, Config::BUILT_IN, Config::BUILT_IN],
        ];
    }
}
