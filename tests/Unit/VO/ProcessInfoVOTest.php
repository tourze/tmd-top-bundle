<?php

namespace Tourze\TmdTopBundle\Tests\Unit\VO;

use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\VO\ProcessInfoVO;

class ProcessInfoVOTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $vo = new ProcessInfoVO(
            '1234',
            'nginx',
            5,
            10,
            1024,
            2048,
            25.5,
            '中国'
        );

        $this->assertSame('1234', $vo->getPid());
        $this->assertSame('nginx', $vo->getName());
        $this->assertSame(5, $vo->getIpCount());
        $this->assertSame(10, $vo->getConnectionCount());
        $this->assertSame(1024, $vo->getUploadBytes());
        $this->assertSame(2048, $vo->getDownloadBytes());
        $this->assertSame(25.5, $vo->getCpuUsage());
        $this->assertSame('中国', $vo->getRegion());
    }

    public function testFromArray(): void
    {
        $data = ['5678', 'apache2', 3, 8, 512, 1536, 15.8, '美国'];
        $vo = ProcessInfoVO::fromArray($data);

        $this->assertSame('5678', $vo->getPid());
        $this->assertSame('apache2', $vo->getName());
        $this->assertSame(3, $vo->getIpCount());
        $this->assertSame(8, $vo->getConnectionCount());
        $this->assertSame(512, $vo->getUploadBytes());
        $this->assertSame(1536, $vo->getDownloadBytes());
        $this->assertSame(15.8, $vo->getCpuUsage());
        $this->assertSame('美国', $vo->getRegion());
    }

    public function testFromArrayWithMissingData(): void
    {
        $data = [];
        $vo = ProcessInfoVO::fromArray($data);

        $this->assertSame('', $vo->getPid());
        $this->assertSame('', $vo->getName());
        $this->assertSame(0, $vo->getIpCount());
        $this->assertSame(0, $vo->getConnectionCount());
        $this->assertSame(0, $vo->getUploadBytes());
        $this->assertSame(0, $vo->getDownloadBytes());
        $this->assertSame(0.0, $vo->getCpuUsage());
        $this->assertSame('', $vo->getRegion());
    }

    public function testFromArrayWithStringValues(): void
    {
        $data = ['9999', 'mysql', '2', '6', '256', '768', '45.7', '日本'];
        $vo = ProcessInfoVO::fromArray($data);

        $this->assertSame('9999', $vo->getPid());
        $this->assertSame('mysql', $vo->getName());
        $this->assertSame(2, $vo->getIpCount());
        $this->assertSame(6, $vo->getConnectionCount());
        $this->assertSame(256, $vo->getUploadBytes());
        $this->assertSame(768, $vo->getDownloadBytes());
        $this->assertSame(45.7, $vo->getCpuUsage());
        $this->assertSame('日本', $vo->getRegion());
    }

    public function testToArray(): void
    {
        $vo = new ProcessInfoVO(
            '4321',
            'redis',
            1,
            4,
            128,
            384,
            8.2,
            '韩国'
        );

        $expected = [
            '4321',
            'redis',
            1,
            4,
            128,
            384,
            8.2,
            '韩国'
        ];

        $this->assertSame($expected, $vo->toArray());
    }
}
