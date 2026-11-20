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
namespace Fastly\Cdn\Test\Unit\Model\Config;

use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Model\Config\ConfigRewrite;
use Fastly\Cdn\Model\PurgeCache;
use Magento\Config\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\StoreGraphQl\Model\Resolver\Store\ConfigIdentity;
use Magento\StoreGraphQl\Model\Resolver\Store\StoreConfigDataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

class ConfigRewriteTest extends TestCase
{
    private $originalObjectManager;

    protected function setUp(): void
    {
        try {
            $this->originalObjectManager = ObjectManager::getInstance();
        } catch (\RuntimeException $e) {
            $this->originalObjectManager = null;
        }
    }

    protected function tearDown(): void
    {
        if ($this->originalObjectManager !== null) {
            ObjectManager::setInstance($this->originalObjectManager);
            return;
        }
        $instance = (new ReflectionClass(ObjectManager::class))->getProperty('_instance');
        $instance->setValue(null, null);
    }

    public function testConstructorDoesNotRequireStoreGraphQlClasses(): void
    {
        $constructor = (new ReflectionClass(ConfigRewrite::class))->getConstructor();

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : '';
            $this->assertStringStartsNotWith('Magento\StoreGraphQl\\', $typeName, $parameter->getName());
        }
    }

    public function testAfterSaveWithoutAsyncConfigSaveDoesNotSendPurge(): void
    {
        $deploymentConfig = $this->createStub(DeploymentConfig::class);
        $deploymentConfig->method('get')->willReturn(null);
        $purgeCache = $this->createMock(PurgeCache::class);
        $purgeCache->expects($this->never())->method('sendPurgeRequest');

        $configRewrite = new ConfigRewrite(
            $this->createStub(ScopeConfigInterface::class),
            $this->createStub(Api::class),
            $this->createStub(StoreManagerInterface::class),
            $purgeCache,
            $deploymentConfig
        );

        $configRewrite->afterSave($this->createStub(Config::class));
    }

    public function testAfterSaveSendsPurgeWithIdentitiesFromStoreConfig(): void
    {
        if (!class_exists(ConfigIdentity::class)
            || !class_exists(StoreConfigDataProvider::class)
            || !class_exists('Magento\\AsyncConfig\\Setup\\ConfigOptionsList')
        ) {
            $this->markTestSkipped('Magento_StoreGraphQl and Magento_AsyncConfig are not installed');
        }

        $configData = ['store_code' => 'default'];
        $store = $this->createStub(StoreInterface::class);

        $provider = $this->createMock(StoreConfigDataProvider::class);
        $provider->expects($this->once())->method('getStoreConfigData')->with($store)->willReturn($configData);
        $identity = $this->createMock(ConfigIdentity::class);
        $identity->expects($this->once())->method('getIdentities')->with($configData)
            ->willReturn(['tag_a', 'tag_b', 'tag_a']);

        $objectManager = $this->createStub(ObjectManager::class);
        $objectManager->method('create')->willReturnMap([[ConfigIdentity::class, [], $identity]]);
        $objectManager->method('get')->willReturnMap([[StoreConfigDataProvider::class, $provider]]);
        ObjectManager::setInstance($objectManager);

        $deploymentConfig = $this->createStub(DeploymentConfig::class);
        $deploymentConfig->method('get')->willReturn(true);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        $purgeCache = $this->createMock(PurgeCache::class);
        $purgeCache->expects($this->once())->method('sendPurgeRequest')->with(['tag_a', 'tag_b']);

        $configRewrite = new ConfigRewrite(
            $this->createStub(ScopeConfigInterface::class),
            $this->createStub(Api::class),
            $storeManager,
            $purgeCache,
            $deploymentConfig
        );

        $configRewrite->afterSave($this->createStub(Config::class));
    }
}
