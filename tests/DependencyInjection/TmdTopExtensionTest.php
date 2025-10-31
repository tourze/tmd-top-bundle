<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\TmdTopBundle\DependencyInjection\TmdTopExtension;

/**
 * @internal
 */
#[CoversClass(TmdTopExtension::class)]
final class TmdTopExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private TmdTopExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension = new TmdTopExtension();
    }

    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $this->extension->load([], $container);

        $this->assertTrue($container->hasDefinition('GeoIp2\Database\Reader'));
        $this->assertGreaterThan(0, count($container->getResources()));
    }

    public function testGetAlias(): void
    {
        $alias = $this->extension->getAlias();

        $this->assertSame('tmd_top', $alias);
    }
}
