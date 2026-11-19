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
namespace Fastly\Cdn\Test\Unit\Controller\Adminhtml\FastlyCdn\Purge;

use Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Purge\Quick;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\PurgeCache;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for quick URL purges.
 */
class QuickTest extends TestCase
{
    /**
     * @return void
     * @dataProvider purgeUrlDataProvider
     */
    #[DataProvider('purgeUrlDataProvider')]
    public function testExecutePurgesUrl(string $url, string $expectedUri): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->once())
            ->method('getParam')
            ->with('quick_purge_url', false)
            ->willReturn($url);
        $response = $this->createStub(ResponseInterface::class);
        $messageManager = $this->createStub(ManagerInterface::class);

        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getResponse')->willReturn($response);
        $context->method('getMessageManager')->willReturn($messageManager);

        $config = $this->createStub(Config::class);
        $config->method('getType')->willReturn(Config::FASTLY);
        $config->method('isEnabled')->willReturn(true);

        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn('https://example.com/');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn([$store]);

        $purgeCache = $this->createMock(PurgeCache::class);
        $purgeCache->expects($this->once())
            ->method('sendPurgeRequest')
            ->with($expectedUri)
            ->willReturn(['status' => true]);

        $action = $this->getMockBuilder(Quick::class)
            ->setConstructorArgs([$context, $purgeCache, $storeManager, $config])
            ->onlyMethods(['_redirect'])
            ->getMock();
        $action->expects($this->once())
            ->method('_redirect')
            ->with('*/cache/index')
            ->willReturn($response);

        $this->assertSame($response, $action->execute());
    }

    /**
     * @return array
     */
    public static function purgeUrlDataProvider(): array
    {
        return [
            'with query' => [
                'https://example.com/category?brand=apple',
                'https://example.com/category?brand=apple'
            ],
            'without query' => [
                'https://example.com/category',
                'https://example.com/category'
            ],
            'encoded query parameters' => [
                'https://example.com/category?brand=apple&filter=red%20apple&filter=green%2Bapple',
                'https://example.com/category?brand=apple&filter=red%20apple&filter=green%2Bapple'
            ],
            'bare host' => [
                'https://example.com',
                'https://example.com/'
            ],
            'host with query' => [
                'https://example.com?brand=apple',
                'https://example.com/?brand=apple'
            ],
            'query with fragment' => [
                'https://example.com/category?brand=apple#products',
                'https://example.com/category?brand=apple'
            ]
        ];
    }
}
