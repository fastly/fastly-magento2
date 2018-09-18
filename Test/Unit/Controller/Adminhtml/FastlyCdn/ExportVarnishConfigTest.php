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

/**
 * Test class for \Magento\PageCache\Controller\Adminhtml/PageCache
 */
namespace Fastly\Cdn\Test\Unit\Controller\Adminhtml\PageCache;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class ExportVarnishConfigTest
 *
 * @package Fastly\Cdn\Test\Unit\Controller\Adminhtml\PageCache
 */
class ExportVarnishConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Framework\App\Response\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $responseMock;

    /**
     * @var \Magento\Framework\App\View|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $viewMock;

    /**
     * @var \Fastly\Cdn\Controller\Adminhtml\FastlyCdn\ExportVarnishConfig
     */
    protected $action;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fileFactoryMock;

    /**
     * @var \Fastly\Cdn\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * Set up before test
     */
    protected function setUp()
    {
        $this->fileFactoryMock = $this->getMockBuilder(
            'Magento\Framework\App\Response\Http\FileFactory'
        )->disableOriginalConstructor()->getMock();
        $this->configMock = $this->getMockBuilder   (
            'Fastly\Cdn\Model\Config'
        )->disableOriginalConstructor()->getMock();
        $contextMock = $this->getMockBuilder(
            'Magento\Backend\App\Action\Context'
        )->disableOriginalConstructor()->getMock();

        $this->requestMock = $this->getMockBuilder(
            'Magento\Framework\App\Request\Http'
        )->disableOriginalConstructor()->getMock();
        $this->responseMock = $this->getMockBuilder(
            'Magento\Framework\App\Response\Http'
        )->disableOriginalConstructor()->getMock();
        $this->viewMock = $this->getMockBuilder('Magento\Framework\App\View')->disableOriginalConstructor()->getMock();

        $contextMock->expects($this->any())->method('getRequest')->will($this->returnValue($this->requestMock));
        $contextMock->expects($this->any())->method('getResponse')->will($this->returnValue($this->responseMock));
        $contextMock->expects($this->any())->method('getView')->will($this->returnValue($this->viewMock));

        $this->action = new \Fastly\Cdn\Controller\Adminhtml\FastlyCdn\ExportVarnishConfig(
            $contextMock,
            $this->fileFactoryMock,
            $this->configMock
        );
    }

    public function testExportVarnishConfigAction()
    {
        $fileContent = 'some content';
        $filename = 'fastly_magento2_varnish.vcl';
        $responseMock = $this->getMockBuilder(
            'Magento\Framework\App\ResponseInterface'
        )->disableOriginalConstructor()->getMock();

        $this->configMock->expects($this->once())->method('getVclFile')->will($this->returnValue($fileContent));
        $this->fileFactoryMock->expects(
            $this->once()
        )->method(
            'create'
        )->with(
            $this->equalTo($filename),
            $this->equalTo($fileContent),
            $this->equalTo(DirectoryList::VAR_DIR)
        )->will(
            $this->returnValue($responseMock)
        );

        if (method_exists($this->action, 'execute')) {
            $result = $this->action->execute();
        } else {
            $result = $this->action->executeInternal();
        }
        $this->assertInstanceOf('Magento\Framework\App\ResponseInterface', $result);
    }
}
