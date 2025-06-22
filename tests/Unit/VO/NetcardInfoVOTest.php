<?php

namespace Tourze\TmdTopBundle\Tests\Unit\VO;

use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\VO\NetcardInfoVO;

class NetcardInfoVOTest extends TestCase
{
    public function testConstruct_withValidData(): void
    {
        $name = 'eth0';
        $uploadBytes = 1000;
        $downloadBytes = 2000;
        
        $netcardInfo = new NetcardInfoVO($name, $uploadBytes, $downloadBytes);
        
        $this->assertSame($name, $netcardInfo->getName());
        $this->assertSame($uploadBytes, $netcardInfo->getUploadBytes());
        $this->assertSame($downloadBytes, $netcardInfo->getDownloadBytes());
    }
    
    public function testFromArray_withValidData(): void
    {
        $data = ['eth0', 1000, 2000];
        
        $netcardInfo = NetcardInfoVO::fromArray($data);
        
        $this->assertSame('eth0', $netcardInfo->getName());
        $this->assertSame(1000, $netcardInfo->getUploadBytes());
        $this->assertSame(2000, $netcardInfo->getDownloadBytes());
    }
    
    public function testFromArray_withMissingData(): void
    {
        $data = ['eth0'];
        
        $netcardInfo = NetcardInfoVO::fromArray($data);
        
        $this->assertSame('eth0', $netcardInfo->getName());
        $this->assertSame(0, $netcardInfo->getUploadBytes());
        $this->assertSame(0, $netcardInfo->getDownloadBytes());
    }
    
    public function testFromArray_withEmptyArray(): void
    {
        $data = [];
        
        $netcardInfo = NetcardInfoVO::fromArray($data);
        
        $this->assertSame('', $netcardInfo->getName());
        $this->assertSame(0, $netcardInfo->getUploadBytes());
        $this->assertSame(0, $netcardInfo->getDownloadBytes());
    }
    
    public function testToArray_returnsCorrectArray(): void
    {
        $netcardInfo = new NetcardInfoVO('eth0', 1000, 2000);
        
        $result = $netcardInfo->toArray();
        
        $this->assertCount(3, $result);
        $this->assertSame('eth0', $result[0]);
        $this->assertSame(1000, $result[1]);
        $this->assertSame(2000, $result[2]);
    }
} 