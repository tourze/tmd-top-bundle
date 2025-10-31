<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests\Factory;

use GeoIp2\Database\Reader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\Factory\GeoIP2Factory;

/**
 * @internal
 */
#[CoversClass(GeoIP2Factory::class)]
final class GeoIP2FactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $factory = new GeoIP2Factory();
        $reader = $factory->create();

        $this->assertInstanceOf(Reader::class, $reader);
    }

    public function testCreateReturnsDifferentInstances(): void
    {
        $factory = new GeoIP2Factory();
        $reader1 = $factory->create();
        $reader2 = $factory->create();

        $this->assertInstanceOf(Reader::class, $reader1);
        $this->assertInstanceOf(Reader::class, $reader2);
        $this->assertNotSame($reader1, $reader2);
    }
}
