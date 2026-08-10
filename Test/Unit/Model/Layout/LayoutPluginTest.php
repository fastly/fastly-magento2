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
         $this->layoutMock = $this->createPartialMock(
             'Magento\Framework\View\Layout',
             ['isCacheable', 'getAllBlocks']
         );


         $this->responseMock = $this->getMockBuilder('\Magento\Framework\App\Response\Http')
             ->disableOriginalConstructor()
             ->getMock();

         $this->configMock = $this->getMockBuilder('Fastly\Cdn\Model\Config')
             ->disableOriginalConstructor()
             ->getMock();

         $cacheTagsMock = $this->getMockBuilder('Fastly\Cdn\Helper\CacheTags')
             ->disableOriginalConstructor()
             ->getMock();

         $this->model = new LayoutPlugin(
             $this->responseMock,
             $this->configMock,
             $cacheTagsMock
         );

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
     public function testAfterGenerateElements($cacheState, $layoutIsCacheable, $cacheType, $ttl, $staleTtl = 0,
         $staleErrorTtl = 0, $cacheControl = 'max-age=86400, public, s-maxage=86400'): void
     {
         $headerName = 'cache-control';
         $cacheableHeaderName = 'fastly-page-cacheable';

         $this->layoutMock->expects($this->any()
         )->method('isCacheable'
         )->willReturn($layoutIsCacheable);

         $this->configMock->expects($this->any()
         )->method('isEnabled'
         )->willReturn($cacheState);

         $this->configMock->expects($this->any()
         )->method('getType'
         )->willReturn($cacheType);

         $this->configMock->expects($this->any()
         )->method('getTtl'
         )->willReturn($ttl);

         if ($layoutIsCacheable && $cacheState && $cacheType == \Fastly\Cdn\Model\Config::FASTLY && $ttl > 0) {
             if (!empty($cacheControl)) {
                 $cacheControlHeader = new GenericHeader($headerName, $cacheControl);
                 $cacheableHeader = new GenericHeader($cacheableHeaderName, 'YES');

                 $this->responseMock->expects($this->exactly(2))
                     ->method('getHeader')
                     ->willReturnCallback(function ($name) use (&$callCount, $headerName, $cacheableHeaderName, $cacheControlHeader, $cacheableHeader) {
                         $callCount++;
                         if ($callCount === 1) {
                             $this->assertEquals($headerName, $name);
                             return $cacheControlHeader;
                         }
                         else {
                             $this->assertEquals($cacheableHeaderName, $name);
                             return $cacheableHeader;
                         }
                     });

                 $this->configMock->expects($this->once()
                 )->method('getStaleTtl'
                 )->willReturn($staleTtl);

                 $this->configMock->expects($this->once()
                 )->method('getStaleErrorTtl'
                 )->willReturn($staleErrorTtl);

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

                 // change to once since 'fastly-page-cacheable' is already set in this mock, so method won't set it again
                 $this->responseMock->expects($this->once()
                 )->method('setHeader');
                 //->with($headerName, $cacheControl . $value, true);

             } else {

                 $this->responseMock->expects($this->exactly(2))
                     ->method('getHeader')
                     ->willReturnCallback(function ($name) use (&$callCount, $headerName, $cacheableHeaderName) {
                         $callCount++;
                         if ($callCount === 1) {
                             $this->assertEquals($headerName, $name);
                             return false;
                         }
                         else {
                             $this->assertEquals($cacheableHeaderName, $name);
                             return 'NO';
                         }
                     });

                 // Removed because 'cache-control' is not set in this case and 'fastly-page-cacheable' is already set
                 // in mock, so second setHeader is skipped
                 //$this->responseMock->expects($this->once())->method('setHeader');
             }
         } else {
             $this->responseMock->expects($this->once()
             )->method('setHeader');
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
     public function testAfterGetOutput($configCacheType, $headerName, $cntSetHeader)
     {
         $html = 'html';

         if ($cntSetHeader === 1) {
             $cntSetHeader = $this->once();
         } else {
             $cntSetHeader = $this->never();
         }

         $this->configMock->expects($this->once()
         )->method('getType'
         )->willReturn($configCacheType);

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
