<?php

namespace Tourze\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\TmdTopBundle\DependencyInjection\TmdTopExtension;

class TmdTopExtensionTest extends TestCase
{
    private TmdTopExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new TmdTopExtension();
    }

    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $this->extension->load([], $container);

        $this->assertTrue($container->hasDefinition('Tourze\TmdTopBundle\Service\NetworkMonitor'));
        $this->assertTrue($container->hasDefinition('Tourze\TmdTopBundle\Factory\GeoIP2Factory'));
    }

    public function testGetAlias(): void
    {
        $alias = $this->extension->getAlias();

        $this->assertSame('tmd_top', $alias);
    }
}
