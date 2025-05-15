<?php

namespace Tourze\TmdTopBundle\Tests\Unit\VO;

use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\VO\ProcessInfoVO;

class ProcessInfoVOTest extends TestCase
{
    public function testConstruct_withValidData(): void
    {
        $pid = '1234';
        $name = 'nginx';
        $ipCount = 10;
        $connectionCount = 100;
        $uploadBytes = 1000;
        $downloadBytes = 2000;
        $cpuUsage = 1.5;
        $region = '北京,上海';
        
        $processInfo = new ProcessInfoVO(
            $pid,
            $name,
            $ipCount,
            $connectionCount,
            $uploadBytes,
            $downloadBytes,
            $cpuUsage,
            $region
        );
        
        $this->assertSame($pid, $processInfo->getPid());
        $this->assertSame($name, $processInfo->getName());
        $this->assertSame($ipCount, $processInfo->getIpCount());
        $this->assertSame($connectionCount, $processInfo->getConnectionCount());
        $this->assertSame($uploadBytes, $processInfo->getUploadBytes());
        $this->assertSame($downloadBytes, $processInfo->getDownloadBytes());
        $this->assertSame($cpuUsage, $processInfo->getCpuUsage());
        $this->assertSame($region, $processInfo->getRegion());
    }
    
    public function testFromArray_withValidData(): void
    {
        $data = [
            '1234',
            'nginx',
            10,
            100,
            1000,
            2000,
            1.5,
            '北京,上海'
        ];
        
        $processInfo = ProcessInfoVO::fromArray($data);
        
        $this->assertSame('1234', $processInfo->getPid());
        $this->assertSame('nginx', $processInfo->getName());
        $this->assertSame(10, $processInfo->getIpCount());
        $this->assertSame(100, $processInfo->getConnectionCount());
        $this->assertSame(1000, $processInfo->getUploadBytes());
        $this->assertSame(2000, $processInfo->getDownloadBytes());
        $this->assertSame(1.5, $processInfo->getCpuUsage());
        $this->assertSame('北京,上海', $processInfo->getRegion());
    }
    
    public function testFromArray_withMissingData(): void
    {
        $data = [
            '1234',
            'nginx'
        ];
        
        $processInfo = ProcessInfoVO::fromArray($data);
        
        $this->assertSame('1234', $processInfo->getPid());
        $this->assertSame('nginx', $processInfo->getName());
        $this->assertSame(0, $processInfo->getIpCount());
        $this->assertSame(0, $processInfo->getConnectionCount());
        $this->assertSame(0, $processInfo->getUploadBytes());
        $this->assertSame(0, $processInfo->getDownloadBytes());
        $this->assertSame(0.0, $processInfo->getCpuUsage());
        $this->assertSame('', $processInfo->getRegion());
    }
    
    public function testFromArray_withEmptyArray(): void
    {
        $data = [];
        
        $processInfo = ProcessInfoVO::fromArray($data);
        
        $this->assertSame('', $processInfo->getPid());
        $this->assertSame('', $processInfo->getName());
        $this->assertSame(0, $processInfo->getIpCount());
        $this->assertSame(0, $processInfo->getConnectionCount());
        $this->assertSame(0, $processInfo->getUploadBytes());
        $this->assertSame(0, $processInfo->getDownloadBytes());
        $this->assertSame(0.0, $processInfo->getCpuUsage());
        $this->assertSame('', $processInfo->getRegion());
    }
    
    public function testFromArray_withNonNumericValues(): void
    {
        $data = [
            '1234',
            'nginx',
            'ten',
            'hundred',
            'thousand',
            'two-thousand',
            'one-point-five',
            '北京,上海'
        ];
        
        $processInfo = ProcessInfoVO::fromArray($data);
        
        $this->assertSame('1234', $processInfo->getPid());
        $this->assertSame('nginx', $processInfo->getName());
        $this->assertSame(0, $processInfo->getIpCount());
        $this->assertSame(0, $processInfo->getConnectionCount());
        $this->assertSame(0, $processInfo->getUploadBytes());
        $this->assertSame(0, $processInfo->getDownloadBytes());
        $this->assertSame(0.0, $processInfo->getCpuUsage());
        $this->assertSame('北京,上海', $processInfo->getRegion());
    }
    
    public function testToArray_returnsCorrectArray(): void
    {
        $processInfo = new ProcessInfoVO(
            '1234',
            'nginx',
            10,
            100,
            1000,
            2000,
            1.5,
            '北京,上海'
        );
        
        $result = $processInfo->toArray();
        
        $this->assertIsArray($result);
        $this->assertCount(8, $result);
        $this->assertSame('1234', $result[0]);
        $this->assertSame('nginx', $result[1]);
        $this->assertSame(10, $result[2]);
        $this->assertSame(100, $result[3]);
        $this->assertSame(1000, $result[4]);
        $this->assertSame(2000, $result[5]);
        $this->assertSame(1.5, $result[6]);
        $this->assertSame('北京,上海', $result[7]);
    }
} 