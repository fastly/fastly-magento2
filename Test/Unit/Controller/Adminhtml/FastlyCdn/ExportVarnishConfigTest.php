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
namespace Fastly\Cdn\Test\Unit\Controller\Adminhtml\FastlyCdn;

use Fastly\Cdn\Controller\Adminhtml\FastlyCdn\ExportVarnishConfig;
use Fastly\Cdn\Model\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\View;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ExportVarnishConfigTest
 *
 * @package Fastly\Cdn\Test\Unit\Controller\Adminhtml\PageCache
 */
class ExportVarnishConfigTest extends TestCase
{
     /**
      * @var \Magento\Framework\App\Request\Http|MockObject
      */
     protected $requestMock;

     /**
      * @var Http|MockObject
      */
     protected $responseMock;

     /**
      * @var View|MockObject
      */
     protected $viewMock;

     /**
      * @var ExportVarnishConfig
      */
     protected $action;

     /**
      * @var FileFactory|MockObject
      */
     protected $fileFactoryMock;

     /**
      * @var Config|MockObject
      */
     protected $configMock;

     /**
      * Set up before test
      */
     protected function setUp(): void
     {
         $this->fileFactoryMock = $this->getMockBuilder(
             'Magento\Framework\App\Response\Http\FileFactory'
         )->disableOriginalConstructor()->getMock();

         $this->configMock = $this->getMockBuilder(
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

         $contextMock->expects($this->any()
         )->method('getRequest'
         )->will($this->returnValue($this->requestMock));

         $contextMock->expects($this->any()
         )->method('getResponse'
         )->will($this->returnValue($this->responseMock));

         $contextMock->expects($this->any()
         )->method('getView'
         )->will($this->returnValue($this->viewMock));

         $this->action = new ExportVarnishConfig(
             $contextMock,
             $this->fileFactoryMock,
             $this->configMock
         );
     }

     public function testExportVarnishConfigAction()
     {
         $fileContent = 'some content';
         $filename = 'varnish.vcl';
         $responseMock = $this->getMockBuilder(
             'Magento\Framework\App\ResponseInterface'
         )->disableOriginalConstructor()->getMock();

         $this->configMock->expects(
             $this->once()
         )->method('getVclFile'
         )->will($this->returnValue($fileContent));

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
