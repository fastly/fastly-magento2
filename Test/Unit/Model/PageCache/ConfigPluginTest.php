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
     * @param \Magento\PageCache\Model\Config $config
     * @param $result
     * @param $expectedOutput
     * @dataProvider afterGetTypeDataProvider
     */
     public function testAfterGetType(\Magento\PageCache\Model\Config $config, $result, $expectedOutput)
     {
         $output = $this->model->afterGetType($config, $result);
         $this->assertSame($expectedOutput, $output);
     }

    /**
     * @return array[]
     */
     public function afterGetTypeDataProvider(): array
     {
         $pageCacheConfigMock = $this->getMockBuilder('Magento\PageCache\Model\Config')->disableOriginalConstructor()->getMock();
         $fastlyConfigMock = $this->getMockBuilder('Fastly\Cdn\Model\Config')->disableOriginalConstructor()->getMock();

         return [
             'Config: Fastly, Cache Type: Fastly, Expected: Fastly' => [$fastlyConfigMock, Config::FASTLY, Config::FASTLY],
             'Config: Fastly, Cache Type: Varnish, Expected: Varnish' => [$fastlyConfigMock, Config::VARNISH, Config::VARNISH],
             'Config: Fastly, Cache Type: Builtin, Expected: Builtin' => [$fastlyConfigMock, Config::BUILT_IN, Config::BUILT_IN],
             'Config: PageCache, Cache Type: Fastly, Expected: Varnish' => [$pageCacheConfigMock, Config::FASTLY, Config::VARNISH],
             'Config: PageCache, Cache Type: Varnish, Expected: Varnish' => [$pageCacheConfigMock, Config::VARNISH, Config::VARNISH],
             'Config: PageCache, Cache Type: Builtin, Expected: Builtin' => [$pageCacheConfigMock, Config::BUILT_IN, Config::BUILT_IN],
         ];
     }
}
