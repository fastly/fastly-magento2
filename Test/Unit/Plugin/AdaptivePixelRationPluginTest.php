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
namespace Fastly\Cdn\Test\Unit\Plugin;

/**
 * Class AdaptivePixelRationPluginTest
 *
 * @package Fastly\Cdn\Test\Unit\Plugin
 */
class AdaptivePixelRationPluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Fastly\Cdn\Model\Config
     */
    private $configMock;

    /**
     * @var \Fastly\Cdn\Plugin\AdaptivePixelRationPlugin
     */
    private $plugin;

    protected function setUp()
    {
        $this->configMock = $this->getMock(\Fastly\Cdn\Model\Config::class, [], [], '', false);
        $this->plugin = new \Fastly\Cdn\Plugin\AdaptivePixelRationPlugin($this->configMock);
    }

    public function testBeforeToHtml()
    {
        $this->configMock->expects($this->once())
            ->method('isImageOptimizationPixelRatioEnabled')
            ->willReturn(true);
        $this->configMock->expects($this->once())
            ->method('getImageOptimizationRatios')
            ->willReturn('2,3');

        $subjectMock = $this->getMock(\Magento\Catalog\Block\Product\Image::class, [], [], '', false);
        $subjectMock->expects($this->once())
            ->method('getData')
            ->with('image_url')
            ->willReturn('http://example.com/image.jpg');
        $expectedSrcset = 'http://example.com/image.jpg?dpr=2 2x,http://example.com/image.jpg?dpr=3 3x';
        $subjectMock->expects($this->once())
            ->method('setData')
            ->with('custom_attributes', ['srcset' => $expectedSrcset]);

        $this->plugin->beforeToHtml($subjectMock);
    }
}
