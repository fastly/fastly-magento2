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

use \Fastly\Cdn\Model\Config;

/**
 * Class ConfigPluginTest
 *
 * @package Fastly\Cdn\Test\Unit\Model\PageCache
 */
class ConfigPluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Fastly\Cdn\Model\Layout\LayoutPlugin
     */
    protected $model;

    /**
     * @var \Magento\Framework\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $responseMock;

    /**
     * @var \Magento\Framework\View\Layout|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $layoutMock;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $configMock;

    public function setUp()
    {
        $this->model = new \Fastly\Cdn\Model\PageCache\ConfigPlugin;
    }

    /**
     * @param object $config
     * @param string $result
     * @param string $expectedOuput
     * @dataProvider afterGetTypeDataProvider
     */
    public function testAfterGetType($config, $result, $expectedOutput)
    {
        $output = $this->model->afterGetType($config, $result);
        $this->assertSame($expectedOutput, $output);
    }

    public function afterGetTypeDataProvider()
    {
        $pageCacheConfigMock = $this->getMock('Magento\PageCache\Model\Config', [], [], '', false);
        $fastlyConfigMock = $this->getMock('Fastly\Cdn\Model\Config', [], [], '', false);

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
