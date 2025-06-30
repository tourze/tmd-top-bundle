<?php

namespace Tourze\Tests\VO;

use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\VO\ServiceInfoVO;

class ServiceInfoVOTest extends TestCase
{
    public function testConstructor(): void
    {
        $pid = '1234';
        $serviceName = 'nginx';
        $ip = '127.0.0.1';
        $port = '80';
        $ipCount = 5;
        $connectionCount = 10;
        $uploadBytes = 1024;
        $downloadBytes = 2048;
        $cpuUsage = 15.5;
        $memoryUsage = 25.3;

        $vo = new ServiceInfoVO($pid, $serviceName, $ip, $port, $ipCount, $connectionCount, $uploadBytes, $downloadBytes, $cpuUsage, $memoryUsage);

        $this->assertSame($pid, $vo->getPid());
        $this->assertSame($serviceName, $vo->getServiceName());
        $this->assertSame($ip, $vo->getIp());
        $this->assertSame($port, $vo->getPort());
        $this->assertSame($ipCount, $vo->getIpCount());
        $this->assertSame($connectionCount, $vo->getConnectionCount());
        $this->assertSame($uploadBytes, $vo->getUploadBytes());
        $this->assertSame($downloadBytes, $vo->getDownloadBytes());
        $this->assertSame($cpuUsage, $vo->getCpuUsage());
        $this->assertSame($memoryUsage, $vo->getMemoryUsage());
    }

    public function testFromArray(): void
    {
        $data = [
            '1234',
            'nginx',
            '127.0.0.1',
            '80',
            5,
            10,
            1024,
            2048,
            15.5,
            25.3,
        ];

        $vo = ServiceInfoVO::fromArray($data);

        $this->assertSame($data[0], $vo->getPid());
        $this->assertSame($data[1], $vo->getServiceName());
        $this->assertSame($data[2], $vo->getIp());
        $this->assertSame($data[3], $vo->getPort());
        $this->assertSame($data[4], $vo->getIpCount());
        $this->assertSame($data[5], $vo->getConnectionCount());
        $this->assertSame($data[6], $vo->getUploadBytes());
        $this->assertSame($data[7], $vo->getDownloadBytes());
        $this->assertSame($data[8], $vo->getCpuUsage());
        $this->assertSame($data[9], $vo->getMemoryUsage());
    }

    public function testToArray(): void
    {
        $pid = '1234';
        $serviceName = 'nginx';
        $ip = '127.0.0.1';
        $port = '80';
        $ipCount = 5;
        $connectionCount = 10;
        $uploadBytes = 1024;
        $downloadBytes = 2048;
        $cpuUsage = 15.5;
        $memoryUsage = 25.3;

        $vo = new ServiceInfoVO($pid, $serviceName, $ip, $port, $ipCount, $connectionCount, $uploadBytes, $downloadBytes, $cpuUsage, $memoryUsage);
        $array = $vo->toArray();

        $this->assertSame([
            $pid,
            $serviceName,
            $ip,
            $port,
            $ipCount,
            $connectionCount,
            $uploadBytes,
            $downloadBytes,
            $cpuUsage,
            $memoryUsage,
        ], $array);
    }

    public function testFromArrayWithMissingKeys(): void
    {
        $data = [
            '1234',
            'nginx',
        ];

        $vo = ServiceInfoVO::fromArray($data);

        $this->assertSame($data[0], $vo->getPid());
        $this->assertSame($data[1], $vo->getServiceName());
        $this->assertSame('', $vo->getIp());
        $this->assertSame('', $vo->getPort());
        $this->assertSame(0, $vo->getIpCount());
        $this->assertSame(0, $vo->getConnectionCount());
        $this->assertSame(0, $vo->getUploadBytes());
        $this->assertSame(0, $vo->getDownloadBytes());
        $this->assertSame(0.0, $vo->getCpuUsage());
        $this->assertSame(0.0, $vo->getMemoryUsage());
    }
} 