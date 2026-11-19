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

use Fastly\Cdn\Model\Layout\LayoutPlugin;
use Laminas\Http\Header\GenericHeader;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Layout;
use Magento\PageCache\Model\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class LayoutPluginTest
 *
 * @package Fastly\Cdn\Test\Unit\Model\Layout
 */
class LayoutPluginTest extends TestCase
{
     /**
      * @var LayoutPlugin
      */
     protected $model;

     /**
      * @var ResponseInterface|MockObject
      */
     protected $responseMock;

     /**
      * @var Layout|MockObject
      */
     protected $layoutMock;

     /**
      * @var ScopeConfigInterface
      */
     protected $configMock;

    /**
     * @var string
     */
     protected $moduleVersion;

    public function setUp(): void
     {
         $this->layoutMock = $this->createStub(Layout::class);
         $this->responseMock = $this->createMock(\Magento\Framework\App\Response\Http::class);
         $this->configMock = $this->createStub(\Fastly\Cdn\Model\Config::class);
         $this->model = new LayoutPlugin($this->responseMock, $this->configMock);

         $this->moduleVersion = json_decode(
             file_get_contents(__DIR__ . '/../../../../composer.json'),
             true
         )['version'];
     }

     /**
      * @param $cacheState
      * @param $layoutIsCacheable
      * @param $cacheType
      * @param $ttl
      * @param $staleTtl
      * @param $staleErrorTtl
      * @param $cacheControl
      * @dataProvider afterGenerateElementsDataProvider
      */
     #[DataProvider('afterGenerateElementsDataProvider')]
     public function testAfterGenerateElements(
         $cacheState,
         $layoutIsCacheable,
         $cacheType,
         $ttl,
         $staleTtl = 0,
         $staleErrorTtl = 0,
         $cacheControl = 'max-age=86400, public, s-maxage=86400'
     ): void {
         $this->layoutMock->method('isCacheable')->willReturn($layoutIsCacheable);
         $this->configMock->method('isEnabled')->willReturn($cacheState);
         $this->configMock->method('getType')->willReturn($cacheType);
         $this->configMock->method('getTtl')->willReturn($ttl);
         $this->configMock->method('getStaleTtl')->willReturn($staleTtl);
         $this->configMock->method('getStaleErrorTtl')->willReturn($staleErrorTtl);

         if ($layoutIsCacheable && $cacheState && $cacheType == \Fastly\Cdn\Model\Config::FASTLY && $ttl > 0) {
             $cacheControlHeader = $cacheControl ? new GenericHeader('cache-control', $cacheControl) : false;
             $cacheableHeader = new GenericHeader('fastly-page-cacheable', 'YES');
             $this->responseMock->expects($this->exactly(2))
                 ->method('getHeader')
                 ->willReturnMap([
                     ['cache-control', $cacheControlHeader],
                     ['fastly-page-cacheable', $cacheableHeader]
                 ]);

             if ($cacheControl) {
                 if ($staleTtl) {
                     $cacheControl .= ', stale-while-revalidate=' . $staleTtl;
                 }
                 if ($staleErrorTtl) {
                     $cacheControl .= ', stale-if-error=' . $staleErrorTtl;
                 }
                 $this->responseMock->expects($this->once())
                     ->method('setHeader')
                     ->with('cache-control', $cacheControl, true);
             } else {
                 $this->responseMock->expects($this->never())->method('setHeader');
             }
         } else {
             $this->responseMock->expects($this->once())
                 ->method('getHeader')
                 ->with('fastly-page-cacheable')
                 ->willReturn(false);
             $this->responseMock->expects($this->once())
                 ->method('setHeader')
                 ->with('fastly-page-cacheable', $layoutIsCacheable ? 'YES' : 'NO');
         }
         $this->model->afterGenerateElements($this->layoutMock);
     }

    /**
     * @return array[]
     */
    public static function afterGenerateElementsDataProvider(): array
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
      * @param $headerName
      * @param $cntSetHeader
      * @dataProvider afterGetOutputDataProvider
      */
     #[DataProvider('afterGetOutputDataProvider')]
     public function testAfterGetOutput($configCacheType, $headerName, $cntSetHeader)
     {
         $html = 'html';

         if ($cntSetHeader === 1) {
             $cntSetHeader = $this->once();
         } else {
             $cntSetHeader = $this->never();
         }

         $this->configMock->method('getType')->willReturn($configCacheType);

         $this->responseMock->expects($cntSetHeader
         )->method('setHeader'
         )->with($headerName, $this->moduleVersion, true);

         $output = $this->model->afterGetOutput($this->layoutMock, $html);
         $this->assertSame($output, $html);
     }

    /**
     * @return array[]
     */
    public static function afterGetOutputDataProvider(): array
    {
        $headerName = 'Fastly-Module-Enabled';

        return [
            'Fastly, getHeader: Yes, setHeader: Yes' =>
                [\Fastly\Cdn\Model\Config::FASTLY, $headerName, 1],
            'Fastly, getHeader: Yes, setHeader: Yes (updated)' =>
                [\Fastly\Cdn\Model\Config::FASTLY, $headerName, 1],
            'Varnish, getHeader: No, setHeader: No' =>
                [Config::VARNISH, $headerName, 0]
        ];
    }
}
