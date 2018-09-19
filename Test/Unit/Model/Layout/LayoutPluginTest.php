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
namespace Fastly\Cdn\Test\Unit\Model\Layout;

/**
 * Class LayoutPluginTest
 *
 * @package Fastly\Cdn\Test\Unit\Model\Layout
 */
class LayoutPluginTest extends \PHPUnit_Framework_TestCase
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
        $this->layoutMock = $this->getMockForAbstractClass(
            'Magento\Framework\View\Layout',
            [],
            '',
            false,
            true,
            true,
            ['isCacheable', 'getAllBlocks']
        );
        $this->responseMock = $this->getMock('\Magento\Framework\App\Response\Http', [], [], '', false);
        $this->configMock = $this->getMock('Fastly\Cdn\Model\Config', [], [], '', false);

        $this->model = new \Fastly\Cdn\Model\Layout\LayoutPlugin(
            $this->responseMock,
            $this->configMock
        );
    }

    /**
     * @param $cacheState
     * @param $layoutIsCacheable
     * @param $cacheType
     * @param $ttl
     * @param $staleTtl
     * @param $staleErrorTtl
     * @param $cacheControl
     * @dataProvider afterGenerateXmlDataProvider
     */
    public function testAfterGenerateXml($cacheState, $layoutIsCacheable, $cacheType, $ttl, $staleTtl = 0,
        $staleErrorTtl = 0, $cacheControl = 'max-age=86400, public, s-maxage=86400')
    {
        $headerName = 'cache-control';
        $result = 'test';

        $this->layoutMock->expects($this->once())->method('isCacheable')->will($this->returnValue($layoutIsCacheable));
        $this->configMock->expects($this->any())->method('isEnabled')->will($this->returnValue($cacheState));
        $this->configMock->expects($this->any())->method('getType')->will($this->returnValue($cacheType));
        $this->configMock->expects($this->any())->method('getTtl')->will($this->returnValue($ttl));

        if ($layoutIsCacheable && $cacheState && $cacheType == \Fastly\Cdn\Model\Config::FASTLY && $ttl > 0) {
            if (!empty($cacheControl)) {
                $cacheControlHeader = new \Zend\Http\Header\GenericHeader($headerName, $cacheControl);
                $this->responseMock->expects($this->once())->method('getHeader')->with($headerName)
                    ->will($this->returnValue($cacheControlHeader));
                $this->configMock->expects($this->once())->method('getStaleTtl')->will($this->returnValue($staleTtl));
                $this->configMock->expects($this->once())->method('getStaleErrorTtl')
                    ->will($this->returnValue($staleErrorTtl));

                $value = '';
                if ($staleTtl && $staleErrorTtl) {
                    $value = sprintf(', stale-while-revalidate=%s, stale-if-error=%s', $staleTtl, $staleErrorTtl);
                }
                if ($staleTtl == 0 && $staleErrorTtl) {
                    $value = sprintf(', stale-if-error=%s', $staleErrorTtl);
                }
                if ($staleTtl && $staleErrorTtl == 0) {
                    $value = sprintf(', stale-while-revalidate=%s', $staleTtl);
                }

                $this->responseMock->expects($this->once())->method('setHeader')
                    ->with($headerName, $cacheControl . $value, true);
            } else {
                $this->responseMock->expects($this->once())->method('getHeader')->with($headerName)
                    ->will($this->returnValue(false));
                $this->responseMock->expects($this->never())->method('setHeader');
            }
        } else {
            $this->responseMock->expects($this->never())->method('setHeader');
        }
        $output = $this->model->afterGenerateXml($this->layoutMock, $result);
        $this->assertSame($result, $output);
    }

    public function afterGenerateXmlDataProvider()
    {
        return [
            'Full_cache state is true, Layout is cache-able, Fastly, TTL > 0, StaleTTL > 0, StaleErrorTTL > 0' =>
                [true, true, \Fastly\Cdn\Model\Config::FASTLY, 1, 1, 1],
            'Full_cache state is true, Layout is cache-able, Fastly, TTL > 0, StaleTTL > 0, StaleErrorTTL = 0' =>
                [true, true, \Fastly\Cdn\Model\Config::FASTLY, 1, 1, 0],
            'Full_cache state is true, Layout is cache-able, Fastly, TTL > 0, StaleTTL = 0, StaleErrorTTL > 0' =>
                [true, true, \Fastly\Cdn\Model\Config::FASTLY, 1, 0, 1],
            'Full_cache state is true, Layout is cache-able, Fastly, TTL > 0, StaleTTL = 0, StaleErrorTTL = 0' =>
                [true, true, \Fastly\Cdn\Model\Config::FASTLY, 1, 0, 0],
            'Full_cache state is true, Layout is cache-able, Fastly, TTL = 0' =>
                [true, true,  \Fastly\Cdn\Model\Config::FASTLY, 0],
            'Full_cache state is true, Layout is cache-able, Varnish, TTL > 0' =>
                [true, true, \Fastly\Cdn\Model\Config::VARNISH, 1],
            'Full_cache state is true, Layout is cache-able, Varnish, TTL = 0' =>
                [true, true, \Fastly\Cdn\Model\Config::VARNISH, 0],
            'Full_cache state is true, Layout is not cache-able, Fastly, TTL > 0' =>
                [true, false, \Fastly\Cdn\Model\Config::FASTLY, 1],
            'Full_cache state is false, Layout is not cache-able, Fastly, TTL > 0' =>
                [false, false, \Fastly\Cdn\Model\Config::FASTLY, 1],
            'Full_cache state is false, Layout is cache-able, Fastly, TTL > 0' =>
                [false, true, \Fastly\Cdn\Model\Config::FASTLY, 1],
            'Full_cache state is true, Layout is cache-able, Fastly, TTL > 0, cache-control empty' =>
                [true, true, \Fastly\Cdn\Model\Config::FASTLY, 1, 1, 1, ''],
        ];
    }

    /**
     * @param $configCacheType
     * @param $cntGetHeader
     * @param $headerName
     * @param $cacheControlHeader
     * @param $cntSetHeader
     * @dataProvider afterGetOutputDataProvider
     */
    public function testAfterGetOutput($configCacheType, $cntGetHeader, $headerName, $cacheControlHeader, $cntSetHeader)
    {
        $html = 'html';
        $modifiedTags = 'identity1 identity2';

        $this->configMock->expects($this->once())->method('getType')->will($this->returnValue($configCacheType));

        $this->responseMock->expects($cntGetHeader)->method('getHeader')->with($headerName)
            ->will($this->returnValue($cacheControlHeader));

        $this->responseMock->expects($cntSetHeader)->method('setHeader')->with($headerName, $modifiedTags, true);

        $output = $this->model->afterGetOutput($this->layoutMock, $html);
        $this->assertSame($output, $html);
    }

    public function afterGetOutputDataProvider()
    {
        $headerName = 'X-Magento-Tags';
        $tags = 'identity1,identity2';

        $cacheControlHeader = new \Zend\Http\Header\GenericHeader($headerName, $tags);

        return [
            'Fastly, getHeader: Yes, setHeader: Yes' =>
                [\Fastly\Cdn\Model\Config::FASTLY, $this->once(), $headerName,$cacheControlHeader, $this->once()],
            'Fastly, getHeader: Yes, setHeader: No' =>
                [\Fastly\Cdn\Model\Config::FASTLY, $this->once(), $headerName,false, $this->never()],
            'Varnish, getHeader: No, setHeader: No' =>
                [\Magento\PageCache\Model\Config::VARNISH, $this->never(), $headerName, false, $this->never()]
        ];
    }
}
