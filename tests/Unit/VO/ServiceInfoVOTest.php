<?php

namespace Tourze\TmdTopBundle\Tests\Unit\VO;

use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\VO\ServiceInfoVO;

class ServiceInfoVOTest extends TestCase
{
    public function testConstruct_withValidData(): void
    {
        $pid = '1234';
        $serviceName = 'nginx';
        $ip = '127.0.0.1';
        $port = '80';
        $ipCount = 10;
        $connectionCount = 100;
        $uploadBytes = 1000;
        $downloadBytes = 2000;
        $cpuUsage = 1.5;
        $memoryUsage = 2.5;
        
        $serviceInfo = new ServiceInfoVO(
            $pid,
            $serviceName,
            $ip,
            $port,
            $ipCount,
            $connectionCount,
            $uploadBytes,
            $downloadBytes,
            $cpuUsage,
            $memoryUsage
        );
        
        $this->assertSame($pid, $serviceInfo->getPid());
        $this->assertSame($serviceName, $serviceInfo->getServiceName());
        $this->assertSame($ip, $serviceInfo->getIp());
        $this->assertSame($port, $serviceInfo->getPort());
        $this->assertSame($ipCount, $serviceInfo->getIpCount());
        $this->assertSame($connectionCount, $serviceInfo->getConnectionCount());
        $this->assertSame($uploadBytes, $serviceInfo->getUploadBytes());
        $this->assertSame($downloadBytes, $serviceInfo->getDownloadBytes());
        $this->assertSame($cpuUsage, $serviceInfo->getCpuUsage());
        $this->assertSame($memoryUsage, $serviceInfo->getMemoryUsage());
    }
    
    public function testFromArray_withValidData(): void
    {
        $data = [
            '1234',
            'nginx',
            '127.0.0.1',
            '80',
            10,
            100,
            1000,
            2000,
            1.5,
            2.5
        ];
        
        $serviceInfo = ServiceInfoVO::fromArray($data);
        
        $this->assertSame('1234', $serviceInfo->getPid());
        $this->assertSame('nginx', $serviceInfo->getServiceName());
        $this->assertSame('127.0.0.1', $serviceInfo->getIp());
        $this->assertSame('80', $serviceInfo->getPort());
        $this->assertSame(10, $serviceInfo->getIpCount());
        $this->assertSame(100, $serviceInfo->getConnectionCount());
        $this->assertSame(1000, $serviceInfo->getUploadBytes());
        $this->assertSame(2000, $serviceInfo->getDownloadBytes());
        $this->assertSame(1.5, $serviceInfo->getCpuUsage());
        $this->assertSame(2.5, $serviceInfo->getMemoryUsage());
    }
    
    public function testFromArray_withMissingData(): void
    {
        $data = [
            '1234',
            'nginx'
        ];
        
        $serviceInfo = ServiceInfoVO::fromArray($data);
        
        $this->assertSame('1234', $serviceInfo->getPid());
        $this->assertSame('nginx', $serviceInfo->getServiceName());
        $this->assertSame('', $serviceInfo->getIp());
        $this->assertSame('', $serviceInfo->getPort());
        $this->assertSame(0, $serviceInfo->getIpCount());
        $this->assertSame(0, $serviceInfo->getConnectionCount());
        $this->assertSame(0, $serviceInfo->getUploadBytes());
        $this->assertSame(0, $serviceInfo->getDownloadBytes());
        $this->assertSame(0.0, $serviceInfo->getCpuUsage());
        $this->assertSame(0.0, $serviceInfo->getMemoryUsage());
    }
    
    public function testFromArray_withEmptyArray(): void
    {
        $data = [];
        
        $serviceInfo = ServiceInfoVO::fromArray($data);
        
        $this->assertSame('', $serviceInfo->getPid());
        $this->assertSame('', $serviceInfo->getServiceName());
        $this->assertSame('', $serviceInfo->getIp());
        $this->assertSame('', $serviceInfo->getPort());
        $this->assertSame(0, $serviceInfo->getIpCount());
        $this->assertSame(0, $serviceInfo->getConnectionCount());
        $this->assertSame(0, $serviceInfo->getUploadBytes());
        $this->assertSame(0, $serviceInfo->getDownloadBytes());
        $this->assertSame(0.0, $serviceInfo->getCpuUsage());
        $this->assertSame(0.0, $serviceInfo->getMemoryUsage());
    }
    
    public function testFromArray_withNonNumericValues(): void
    {
        $data = [
            '1234',
            'nginx',
            '127.0.0.1',
            '80',
            'ten',
            'hundred',
            'thousand',
            'two-thousand',
            'one-point-five',
            'two-point-five'
        ];
        
        $serviceInfo = ServiceInfoVO::fromArray($data);
        
        $this->assertSame('1234', $serviceInfo->getPid());
        $this->assertSame('nginx', $serviceInfo->getServiceName());
        $this->assertSame('127.0.0.1', $serviceInfo->getIp());
        $this->assertSame('80', $serviceInfo->getPort());
        $this->assertSame(0, $serviceInfo->getIpCount());
        $this->assertSame(0, $serviceInfo->getConnectionCount());
        $this->assertSame(0, $serviceInfo->getUploadBytes());
        $this->assertSame(0, $serviceInfo->getDownloadBytes());
        $this->assertSame(0.0, $serviceInfo->getCpuUsage());
        $this->assertSame(0.0, $serviceInfo->getMemoryUsage());
    }
    
    public function testToArray_returnsCorrectArray(): void
    {
        $serviceInfo = new ServiceInfoVO(
            '1234',
            'nginx',
            '127.0.0.1',
            '80',
            10,
            100,
            1000,
            2000,
            1.5,
            2.5
        );
        
        $result = $serviceInfo->toArray();
        
        $this->assertIsArray($result);
        $this->assertCount(10, $result);
        $this->assertSame('1234', $result[0]);
        $this->assertSame('nginx', $result[1]);
        $this->assertSame('127.0.0.1', $result[2]);
        $this->assertSame('80', $result[3]);
        $this->assertSame(10, $result[4]);
        $this->assertSame(100, $result[5]);
        $this->assertSame(1000, $result[6]);
        $this->assertSame(2000, $result[7]);
        $this->assertSame(1.5, $result[8]);
        $this->assertSame(2.5, $result[9]);
    }
} 