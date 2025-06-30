<?php

namespace Tourze\Tests\VO;

use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\VO\ProcessInfoVO;

class ProcessInfoVOTest extends TestCase
{
    public function testConstructor(): void
    {
        $pid = '1234';
        $name = 'php';
        $ipCount = 5;
        $connectionCount = 10;
        $uploadBytes = 1024;
        $downloadBytes = 2048;
        $cpuUsage = 15.5;
        $region = '中国/北京/北京';

        $vo = new ProcessInfoVO($pid, $name, $ipCount, $connectionCount, $uploadBytes, $downloadBytes, $cpuUsage, $region);

        $this->assertSame($pid, $vo->getPid());
        $this->assertSame($name, $vo->getName());
        $this->assertSame($ipCount, $vo->getIpCount());
        $this->assertSame($connectionCount, $vo->getConnectionCount());
        $this->assertSame($uploadBytes, $vo->getUploadBytes());
        $this->assertSame($downloadBytes, $vo->getDownloadBytes());
        $this->assertSame($cpuUsage, $vo->getCpuUsage());
        $this->assertSame($region, $vo->getRegion());
    }

    public function testFromArray(): void
    {
        $data = [
            '1234',
            'php',
            5,
            10,
            1024,
            2048,
            15.5,
            '中国/北京/北京',
        ];

        $vo = ProcessInfoVO::fromArray($data);

        $this->assertSame($data[0], $vo->getPid());
        $this->assertSame($data[1], $vo->getName());
        $this->assertSame($data[2], $vo->getIpCount());
        $this->assertSame($data[3], $vo->getConnectionCount());
        $this->assertSame($data[4], $vo->getUploadBytes());
        $this->assertSame($data[5], $vo->getDownloadBytes());
        $this->assertSame($data[6], $vo->getCpuUsage());
        $this->assertSame($data[7], $vo->getRegion());
    }

    public function testToArray(): void
    {
        $pid = '1234';
        $name = 'php';
        $ipCount = 5;
        $connectionCount = 10;
        $uploadBytes = 1024;
        $downloadBytes = 2048;
        $cpuUsage = 15.5;
        $region = '中国/北京/北京';

        $vo = new ProcessInfoVO($pid, $name, $ipCount, $connectionCount, $uploadBytes, $downloadBytes, $cpuUsage, $region);
        $array = $vo->toArray();

        $this->assertSame([
            $pid,
            $name,
            $ipCount,
            $connectionCount,
            $uploadBytes,
            $downloadBytes,
            $cpuUsage,
            $region,
        ], $array);
    }

    public function testFromArrayWithMissingKeys(): void
    {
        $data = [
            '1234',
            'php',
        ];

        $vo = ProcessInfoVO::fromArray($data);

        $this->assertSame($data[0], $vo->getPid());
        $this->assertSame($data[1], $vo->getName());
        $this->assertSame(0, $vo->getIpCount());
        $this->assertSame(0, $vo->getConnectionCount());
        $this->assertSame(0, $vo->getUploadBytes());
        $this->assertSame(0, $vo->getDownloadBytes());
        $this->assertSame(0.0, $vo->getCpuUsage());
        $this->assertSame('', $vo->getRegion());
    }
} 