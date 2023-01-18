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
namespace Fastly\Cdn\Test\Unit\Model;

use Fastly\Cdn\Model\Config;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigTest
 *
 * @package Fastly\Cdn\Test\Unit\Model
 */
class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
     protected $_model;

    /**
     * @var MockObject|ScopeConfigInterface
     */
     protected $_coreConfigMock;

    /**
     * @var MockObject|StateInterface
     */
    protected $_cacheState;

    /**
     * @var MockObject|Reader
     */
    protected $moduleReader;

    /**
     * setUp all mocks and data function
     */
    public function setUp(): void
    {
         $readFactoryMock = $this->getMockBuilder('Magento\Framework\Filesystem\Directory\ReadFactory')
             ->disableOriginalConstructor()
             ->getMock();
         $this->_coreConfigMock = $this->getMockBuilder('Magento\Framework\App\Config\ScopeConfigInterface')->getMock();
         $this->_cacheState = $this->getMockForAbstractClass('Magento\Framework\App\Cache\StateInterface');
         $serializer = new Json();
         $modulesDirectoryMock = $this->getMockBuilder('Magento\Framework\Filesystem\Directory\Write')
             ->disableOriginalConstructor()
             ->getMock();
         $vclGeneratorFactoryMock = $this->getMockBuilder('Magento\PageCache\Model\Varnish\VclGeneratorFactory')
             ->disableOriginalConstructor()
             ->getMock();

         $readFactoryMock->expects(
             $this->any()
         )->method(
             'create'
         )->will(
             $this->returnValue($modulesDirectoryMock)
         );

         $modulesDirectoryMock->expects(
             $this->any()
         )->method(
             'readFile'
         )->will(
             $this->returnValue(file_get_contents(__DIR__ . '/_files/test.vcl'))
         );
         $this->_coreConfigMock->expects(
             $this->any()
         )->method(
             'getValue'
         )->will(
             $this->returnValueMap(
                 [
                     [
                         \Magento\PageCache\Model\Config::XML_VARNISH_PAGECACHE_BACKEND_HOST,
                         ScopeInterface::SCOPE_STORE,
                         null,
                         'example.com',
                     ],
                     [
                         \Magento\PageCache\Model\Config::XML_VARNISH_PAGECACHE_BACKEND_PORT,
                         ScopeInterface::SCOPE_STORE,
                         null,
                         '8080'
                     ],
                     [
                         \Magento\PageCache\Model\Config::XML_VARNISH_PAGECACHE_ACCESS_LIST,
                         ScopeInterface::SCOPE_STORE,
                         null,
                         '127.0.0.1, 192.168.0.1,127.0.0.2'
                     ],
                     [
                         \Magento\PageCache\Model\Config::XML_VARNISH_PAGECACHE_DESIGN_THEME_REGEX,
                         ScopeInterface::SCOPE_STORE,
                         null,
                         $serializer->serialize([['regexp' => '(?i)pattern', 'value' => 'value_for_pattern']])
                     ],
                 ]
             )
         );

         $this->moduleReader = $this->getMockBuilder('Magento\Framework\Module\Dir\Reader')
             ->disableOriginalConstructor()
             ->getMock();

         $this->_model = new Config(
             $readFactoryMock,
             $this->_coreConfigMock,
             $this->_cacheState,
             $this->moduleReader,
             $vclGeneratorFactoryMock
         );
     }

     /**
      * test for getVcl method
      */
     public function testGetVclFile()
     {
         $this->moduleReader->expects($this->once())
             ->method('getModuleDir')
             ->willReturn('/magento/app/code/Fastly/CDN');
         $test = $this->_model->getVclFile(Config::VARNISH_4_CONFIGURATION_PATH);
         $this->assertEquals(file_get_contents(__DIR__ . '/_files/result.vcl'), $test);
     }

     public function testGetTll()
     {
         $this->_coreConfigMock->expects($this->once()
         )->method('getValue'
         )->with(Config::XML_PAGECACHE_TTL);

         $this->_model->getTtl();
     }

     public function testGetStaleTtl()
     {
         $this->_coreConfigMock->expects($this->once()
         )->method('getValue'
         )->with(Config::XML_FASTLY_STALE_TTL);

         $this->_model->getStaleTtl();
     }

     public function testGetStaleErrorTtl()
     {
         $this->_coreConfigMock->expects($this->once()
         )->method('getValue'
         )->with(Config::XML_FASTLY_STALE_ERROR_TTL);

         $this->_model->getStaleErrorTtl();
     }
}
