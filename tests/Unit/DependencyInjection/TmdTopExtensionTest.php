<?php

namespace Tourze\TmdTopBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\TmdTopBundle\DependencyInjection\TmdTopExtension;

class TmdTopExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $extension = new TmdTopExtension();
        $container = new ContainerBuilder();

        // 测试加载过程不抛出异常
        $this->expectNotToPerformAssertions();
        $extension->load([], $container);
    }

    public function testLoadWithConfigs(): void
    {
        $extension = new TmdTopExtension();
        $container = new ContainerBuilder();
        $configs = [
            ['some_config' => 'value']
        ];

        // 测试带配置的加载过程不抛出异常
        $this->expectNotToPerformAssertions();
        $extension->load($configs, $container);
    }
}
