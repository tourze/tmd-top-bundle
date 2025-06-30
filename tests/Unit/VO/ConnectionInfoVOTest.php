<?php

namespace Tourze\TmdTopBundle\Tests\Unit\VO;

use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\VO\ConnectionInfoVO;

class ConnectionInfoVOTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $vo = new ConnectionInfoVO(
            '192.168.1.1',
            '8080',
            1024,
            2048,
            '中国/广东/深圳'
        );

        $this->assertSame('192.168.1.1', $vo->getRemoteIp());
        $this->assertSame('8080', $vo->getRemotePort());
        $this->assertSame(1024, $vo->getUploadBytes());
        $this->assertSame(2048, $vo->getDownloadBytes());
        $this->assertSame('中国/广东/深圳', $vo->getLocation());
    }

    public function testFromArray(): void
    {
        $data = ['10.0.0.1', '443', 512, 1536, '美国/加利福尼亚/洛杉矶'];
        $vo = ConnectionInfoVO::fromArray($data);

        $this->assertSame('10.0.0.1', $vo->getRemoteIp());
        $this->assertSame('443', $vo->getRemotePort());
        $this->assertSame(512, $vo->getUploadBytes());
        $this->assertSame(1536, $vo->getDownloadBytes());
        $this->assertSame('美国/加利福尼亚/洛杉矶', $vo->getLocation());
    }

    public function testFromArrayWithMissingData(): void
    {
        $data = [];
        $vo = ConnectionInfoVO::fromArray($data);

        $this->assertSame('', $vo->getRemoteIp());
        $this->assertSame('', $vo->getRemotePort());
        $this->assertSame(0, $vo->getUploadBytes());
        $this->assertSame(0, $vo->getDownloadBytes());
        $this->assertSame('未知', $vo->getLocation());
    }

    public function testToArray(): void
    {
        $vo = new ConnectionInfoVO(
            '203.208.60.1',
            '80',
            256,
            512,
            '美国/华盛顿/西雅图'
        );

        $expected = [
            '203.208.60.1',
            '80',
            256,
            512,
            '美国/华盛顿/西雅图'
        ];

        $this->assertSame($expected, $vo->toArray());
    }
}
