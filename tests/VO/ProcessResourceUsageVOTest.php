<?php

namespace Tourze\Tests\VO;

use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\VO\ProcessResourceUsageVO;

class ProcessResourceUsageVOTest extends TestCase
{
    public function testConstructor(): void
    {
        $cpu = 15.5;
        $mem = 25.3;

        $vo = new ProcessResourceUsageVO($cpu, $mem);

        $this->assertSame($cpu, $vo->getCpu());
        $this->assertSame($mem, $vo->getMem());
    }

    public function testFromArray(): void
    {
        $data = [
            'cpu' => 15.5,
            'mem' => 25.3,
        ];

        $vo = ProcessResourceUsageVO::fromArray($data);

        $this->assertSame($data['cpu'], $vo->getCpu());
        $this->assertSame($data['mem'], $vo->getMem());
    }

    public function testFromArrayWithStringValues(): void
    {
        $data = [
            'cpu' => '15.5',
            'mem' => '25.3',
        ];

        $vo = ProcessResourceUsageVO::fromArray($data);

        $this->assertSame(15.5, $vo->getCpu());
        $this->assertSame(25.3, $vo->getMem());
    }

    public function testFromArrayWithIntegerValues(): void
    {
        $data = [
            'cpu' => 15,
            'mem' => 25,
        ];

        $vo = ProcessResourceUsageVO::fromArray($data);

        $this->assertSame(15.0, $vo->getCpu());
        $this->assertSame(25.0, $vo->getMem());
    }

    public function testToArray(): void
    {
        $cpu = 15.5;
        $mem = 25.3;

        $vo = new ProcessResourceUsageVO($cpu, $mem);
        $array = $vo->toArray();

        $this->assertSame([
            'cpu' => $cpu,
            'mem' => $mem,
        ], $array);
    }

    public function testFromArrayWithMissingKeys(): void
    {
        $data = ['cpu' => 15.5];

        $vo = ProcessResourceUsageVO::fromArray($data);

        $this->assertSame(15.5, $vo->getCpu());
        $this->assertSame(0.0, $vo->getMem());
    }

    public function testFromArrayWithEmptyArray(): void
    {
        $vo = ProcessResourceUsageVO::fromArray([]);

        $this->assertSame(0.0, $vo->getCpu());
        $this->assertSame(0.0, $vo->getMem());
    }

    public function testFromArrayWithInvalidValues(): void
    {
        $data = [
            'cpu' => 'invalid',
            'mem' => null,
        ];

        $vo = ProcessResourceUsageVO::fromArray($data);

        $this->assertSame(0.0, $vo->getCpu());
        $this->assertSame(0.0, $vo->getMem());
    }
} 