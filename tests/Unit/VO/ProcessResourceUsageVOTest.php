<?php

namespace Tourze\TmdTopBundle\Tests\Unit\VO;

use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\VO\ProcessResourceUsageVO;

class ProcessResourceUsageVOTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $vo = new ProcessResourceUsageVO(25.5, 64.2);

        $this->assertSame(25.5, $vo->getCpu());
        $this->assertSame(64.2, $vo->getMem());
    }

    public function testFromArray(): void
    {
        $data = ['cpu' => 15.8, 'mem' => 32.4];
        $vo = ProcessResourceUsageVO::fromArray($data);

        $this->assertSame(15.8, $vo->getCpu());
        $this->assertSame(32.4, $vo->getMem());
    }

    public function testFromArrayWithMissingData(): void
    {
        $data = [];
        $vo = ProcessResourceUsageVO::fromArray($data);

        $this->assertSame(0.0, $vo->getCpu());
        $this->assertSame(0.0, $vo->getMem());
    }

    public function testFromArrayWithStringValues(): void
    {
        $data = ['cpu' => '45.7', 'mem' => '78.9'];
        $vo = ProcessResourceUsageVO::fromArray($data);

        $this->assertSame(45.7, $vo->getCpu());
        $this->assertSame(78.9, $vo->getMem());
    }

    public function testToArray(): void
    {
        $vo = new ProcessResourceUsageVO(12.3, 45.6);

        $expected = [
            'cpu' => 12.3,
            'mem' => 45.6
        ];

        $this->assertSame($expected, $vo->toArray());
    }
}
