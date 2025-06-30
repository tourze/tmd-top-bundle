<?php

namespace Tourze\TmdTopBundle\Tests\Unit\VO;

use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\VO\ServiceInfoVO;

class ServiceInfoVOTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $vo = new ServiceInfoVO(
            '1234',
            'nginx',
            '0.0.0.0',
            '80',
            5,
            10,
            1024,
            2048,
            25.5,
            64.2
        );

        $this->assertSame('1234', $vo->getPid());
        $this->assertSame('nginx', $vo->getServiceName());
        $this->assertSame('0.0.0.0', $vo->getIp());
        $this->assertSame('80', $vo->getPort());
        $this->assertSame(5, $vo->getIpCount());
        $this->assertSame(10, $vo->getConnectionCount());
        $this->assertSame(1024, $vo->getUploadBytes());
        $this->assertSame(2048, $vo->getDownloadBytes());
        $this->assertSame(25.5, $vo->getCpuUsage());
        $this->assertSame(64.2, $vo->getMemoryUsage());
    }

    public function testFromArray(): void
    {
        $data = ['5678', 'apache2', '127.0.0.1', '443', 3, 8, 512, 1536, 15.8, 32.4];
        $vo = ServiceInfoVO::fromArray($data);

        $this->assertSame('5678', $vo->getPid());
        $this->assertSame('apache2', $vo->getServiceName());
        $this->assertSame('127.0.0.1', $vo->getIp());
        $this->assertSame('443', $vo->getPort());
        $this->assertSame(3, $vo->getIpCount());
        $this->assertSame(8, $vo->getConnectionCount());
        $this->assertSame(512, $vo->getUploadBytes());
        $this->assertSame(1536, $vo->getDownloadBytes());
        $this->assertSame(15.8, $vo->getCpuUsage());
        $this->assertSame(32.4, $vo->getMemoryUsage());
    }

    public function testFromArrayWithMissingData(): void
    {
        $data = [];
        $vo = ServiceInfoVO::fromArray($data);

        $this->assertSame('', $vo->getPid());
        $this->assertSame('', $vo->getServiceName());
        $this->assertSame('', $vo->getIp());
        $this->assertSame('', $vo->getPort());
        $this->assertSame(0, $vo->getIpCount());
        $this->assertSame(0, $vo->getConnectionCount());
        $this->assertSame(0, $vo->getUploadBytes());
        $this->assertSame(0, $vo->getDownloadBytes());
        $this->assertSame(0.0, $vo->getCpuUsage());
        $this->assertSame(0.0, $vo->getMemoryUsage());
    }

    public function testFromArrayWithStringValues(): void
    {
        $data = ['9999', 'mysql', '192.168.1.1', '3306', '2', '6', '256', '768', '45.7', '78.9'];
        $vo = ServiceInfoVO::fromArray($data);

        $this->assertSame('9999', $vo->getPid());
        $this->assertSame('mysql', $vo->getServiceName());
        $this->assertSame('192.168.1.1', $vo->getIp());
        $this->assertSame('3306', $vo->getPort());
        $this->assertSame(2, $vo->getIpCount());
        $this->assertSame(6, $vo->getConnectionCount());
        $this->assertSame(256, $vo->getUploadBytes());
        $this->assertSame(768, $vo->getDownloadBytes());
        $this->assertSame(45.7, $vo->getCpuUsage());
        $this->assertSame(78.9, $vo->getMemoryUsage());
    }

    public function testToArray(): void
    {
        $vo = new ServiceInfoVO(
            '4321',
            'redis',
            '*',
            '6379',
            1,
            4,
            128,
            384,
            8.2,
            16.5
        );

        $expected = [
            '4321',
            'redis',
            '*',
            '6379',
            1,
            4,
            128,
            384,
            8.2,
            16.5
        ];

        $this->assertSame($expected, $vo->toArray());
    }
}
