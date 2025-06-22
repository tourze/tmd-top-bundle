<?php

namespace Tourze\TmdTopBundle\Tests\Unit\VO;

use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\VO\ConnectionInfoVO;

class ConnectionInfoVOTest extends TestCase
{
    public function testConstruct_withValidData(): void
    {
        $remoteIp = '192.168.1.1';
        $remotePort = '12345';
        $uploadBytes = 1000;
        $downloadBytes = 2000;
        $location = '北京';
        
        $connectionInfo = new ConnectionInfoVO(
            $remoteIp,
            $remotePort,
            $uploadBytes,
            $downloadBytes,
            $location
        );
        
        $this->assertSame($remoteIp, $connectionInfo->getRemoteIp());
        $this->assertSame($remotePort, $connectionInfo->getRemotePort());
        $this->assertSame($uploadBytes, $connectionInfo->getUploadBytes());
        $this->assertSame($downloadBytes, $connectionInfo->getDownloadBytes());
        $this->assertSame($location, $connectionInfo->getLocation());
    }
    
    public function testFromArray_withValidData(): void
    {
        $data = [
            '192.168.1.1',
            '12345',
            1000,
            2000,
            '北京'
        ];
        
        $connectionInfo = ConnectionInfoVO::fromArray($data);
        
        $this->assertSame('192.168.1.1', $connectionInfo->getRemoteIp());
        $this->assertSame('12345', $connectionInfo->getRemotePort());
        $this->assertSame(1000, $connectionInfo->getUploadBytes());
        $this->assertSame(2000, $connectionInfo->getDownloadBytes());
        $this->assertSame('北京', $connectionInfo->getLocation());
    }
    
    public function testFromArray_withMissingData(): void
    {
        $data = [
            '192.168.1.1',
            '12345'
        ];
        
        $connectionInfo = ConnectionInfoVO::fromArray($data);
        
        $this->assertSame('192.168.1.1', $connectionInfo->getRemoteIp());
        $this->assertSame('12345', $connectionInfo->getRemotePort());
        $this->assertSame(0, $connectionInfo->getUploadBytes());
        $this->assertSame(0, $connectionInfo->getDownloadBytes());
        $this->assertSame('未知', $connectionInfo->getLocation());
    }
    
    public function testFromArray_withEmptyArray(): void
    {
        $data = [];
        
        $connectionInfo = ConnectionInfoVO::fromArray($data);
        
        $this->assertSame('', $connectionInfo->getRemoteIp());
        $this->assertSame('', $connectionInfo->getRemotePort());
        $this->assertSame(0, $connectionInfo->getUploadBytes());
        $this->assertSame(0, $connectionInfo->getDownloadBytes());
        $this->assertSame('未知', $connectionInfo->getLocation());
    }
    
    public function testFromArray_withNonNumericValues(): void
    {
        $data = [
            '192.168.1.1',
            '12345',
            'thousand',
            'two-thousand',
            '北京'
        ];
        
        $connectionInfo = ConnectionInfoVO::fromArray($data);
        
        $this->assertSame('192.168.1.1', $connectionInfo->getRemoteIp());
        $this->assertSame('12345', $connectionInfo->getRemotePort());
        $this->assertSame(0, $connectionInfo->getUploadBytes());
        $this->assertSame(0, $connectionInfo->getDownloadBytes());
        $this->assertSame('北京', $connectionInfo->getLocation());
    }
    
    public function testToArray_returnsCorrectArray(): void
    {
        $connectionInfo = new ConnectionInfoVO(
            '192.168.1.1',
            '12345',
            1000,
            2000,
            '北京'
        );
        
        $result = $connectionInfo->toArray();
        
        $this->assertCount(5, $result);
        $this->assertSame('192.168.1.1', $result[0]);
        $this->assertSame('12345', $result[1]);
        $this->assertSame(1000, $result[2]);
        $this->assertSame(2000, $result[3]);
        $this->assertSame('北京', $result[4]);
    }
} 