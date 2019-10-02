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

/**
 * Class ConfigTest
 *
 * @package Fastly\Cdn\Test\Unit\Model
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Fastly\Cdn\Model\Config
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_coreConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Cache\StateInterface
     */
    protected $_cacheState;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Module\Dir\Reader
     */
    protected $moduleReader;

    /**
     * setUp all mocks and data function
     */
    public function setUp()
    {
        $readFactoryMock = $this->getMock('Magento\Framework\Filesystem\Directory\ReadFactory', [], [], '', false);
        $this->_coreConfigMock = $this->getMock('Magento\Framework\App\Config\ScopeConfigInterface');
        $this->_cacheState = $this->getMockForAbstractClass('Magento\Framework\App\Cache\StateInterface');
        $serializer = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            \Magento\Framework\Serialize\Serializer\Json::class
        );
        $modulesDirectoryMock = $this->getMock(
            'Magento\Framework\Filesystem\Directory\Write',
            [],
            [],
            '',
            false
        );
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
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        null,
                        'example.com',
                    ],
                    [
                        \Magento\PageCache\Model\Config::XML_VARNISH_PAGECACHE_BACKEND_PORT,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        null,
                        '8080'
                    ],
                    [
                        \Magento\PageCache\Model\Config::XML_VARNISH_PAGECACHE_ACCESS_LIST,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        null,
                        '127.0.0.1, 192.168.0.1,127.0.0.2'
                    ],
                    [
                        \Magento\PageCache\Model\Config::XML_VARNISH_PAGECACHE_DESIGN_THEME_REGEX,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        null,
                        $serializer->serialize([['regexp' => '(?i)pattern', 'value' => 'value_for_pattern']])
                    ],
                ]
            )
        );

        $this->moduleReader = $this->getMock('Magento\Framework\Module\Dir\Reader', [], [], '', false);
        $this->_model = new \Fastly\Cdn\Model\Config(
            $readFactoryMock,
            $this->_coreConfigMock,
            $this->_cacheState,
            $this->moduleReader
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
        $test = $this->_model->getVclFile(Config::VARNISH_3_CONFIGURATION_PATH);
        $this->assertEquals(file_get_contents(__DIR__ . '/_files/result.vcl'), $test);
    }

    public function testGetTll()
    {
        $this->_coreConfigMock->expects($this->once())->method('getValue')->with(Config::XML_PAGECACHE_TTL);
        $this->_model->getTtl();
    }

    public function testGetStaleTtl()
    {
        $this->_coreConfigMock->expects($this->once())->method('getValue')->with(Config::XML_FASTLY_STALE_TTL);
        $this->_model->getStaleTtl();
    }

    public function testGetStaleErrorTtl()
    {
        $this->_coreConfigMock->expects($this->once())->method('getValue')->with(Config::XML_FASTLY_STALE_ERROR_TTL);
        $this->_model->getStaleErrorTtl();
    }
}
