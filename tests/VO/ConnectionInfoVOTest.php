<?php

namespace Tourze\Tests\VO;

use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\VO\ConnectionInfoVO;

class ConnectionInfoVOTest extends TestCase
{
    public function testConstructor(): void
    {
        $remoteIp = '192.168.1.1';
        $remotePort = '80';
        $uploadBytes = 1024;
        $downloadBytes = 2048;
        $location = '中国/北京/北京';

        $vo = new ConnectionInfoVO($remoteIp, $remotePort, $uploadBytes, $downloadBytes, $location);

        $this->assertSame($remoteIp, $vo->getRemoteIp());
        $this->assertSame($remotePort, $vo->getRemotePort());
        $this->assertSame($uploadBytes, $vo->getUploadBytes());
        $this->assertSame($downloadBytes, $vo->getDownloadBytes());
        $this->assertSame($location, $vo->getLocation());
    }

    public function testFromArray(): void
    {
        $data = [
            '192.168.1.1',
            '80',
            1024,
            2048,
            '中国/北京/北京',
        ];

        $vo = ConnectionInfoVO::fromArray($data);

        $this->assertSame($data[0], $vo->getRemoteIp());
        $this->assertSame($data[1], $vo->getRemotePort());
        $this->assertSame($data[2], $vo->getUploadBytes());
        $this->assertSame($data[3], $vo->getDownloadBytes());
        $this->assertSame($data[4], $vo->getLocation());
    }

    public function testToArray(): void
    {
        $remoteIp = '192.168.1.1';
        $remotePort = '80';
        $uploadBytes = 1024;
        $downloadBytes = 2048;
        $location = '中国/北京/北京';

        $vo = new ConnectionInfoVO($remoteIp, $remotePort, $uploadBytes, $downloadBytes, $location);
        $array = $vo->toArray();

        $this->assertSame([
            $remoteIp,
            $remotePort,
            $uploadBytes,
            $downloadBytes,
            $location,
        ], $array);
    }

    public function testFromArrayWithMissingKeys(): void
    {
        $data = [
            '192.168.1.1',
            '80',
        ];

        $vo = ConnectionInfoVO::fromArray($data);

        $this->assertSame($data[0], $vo->getRemoteIp());
        $this->assertSame($data[1], $vo->getRemotePort());
        $this->assertSame(0, $vo->getUploadBytes());
        $this->assertSame(0, $vo->getDownloadBytes());
        $this->assertSame('未知', $vo->getLocation());
    }
} 