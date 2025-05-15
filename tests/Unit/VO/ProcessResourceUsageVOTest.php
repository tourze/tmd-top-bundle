<?php

namespace Tourze\TmdTopBundle\Tests\Unit\VO;

use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\VO\ProcessResourceUsageVO;

class ProcessResourceUsageVOTest extends TestCase
{
    public function testConstruct_withValidData(): void
    {
        $cpu = 12.34;
        $mem = 56.78;
        
        $resourceUsage = new ProcessResourceUsageVO($cpu, $mem);
        
        $this->assertSame($cpu, $resourceUsage->getCpu());
        $this->assertSame($mem, $resourceUsage->getMem());
    }
    
    public function testFromArray_withValidData(): void
    {
        $data = [
            'cpu' => 12.34,
            'mem' => 56.78
        ];
        
        $resourceUsage = ProcessResourceUsageVO::fromArray($data);
        
        $this->assertSame(12.34, $resourceUsage->getCpu());
        $this->assertSame(56.78, $resourceUsage->getMem());
    }
    
    public function testFromArray_withMissingData(): void
    {
        $data = [
            'cpu' => 12.34
        ];
        
        $resourceUsage = ProcessResourceUsageVO::fromArray($data);
        
        $this->assertSame(12.34, $resourceUsage->getCpu());
        $this->assertSame(0.0, $resourceUsage->getMem());
    }
    
    public function testFromArray_withEmptyArray(): void
    {
        $data = [];
        
        $resourceUsage = ProcessResourceUsageVO::fromArray($data);
        
        $this->assertSame(0.0, $resourceUsage->getCpu());
        $this->assertSame(0.0, $resourceUsage->getMem());
    }
    
    public function testFromArray_withStringValues(): void
    {
        $data = [
            'cpu' => '12.34',
            'mem' => '56.78'
        ];
        
        $resourceUsage = ProcessResourceUsageVO::fromArray($data);
        
        $this->assertSame(12.34, $resourceUsage->getCpu());
        $this->assertSame(56.78, $resourceUsage->getMem());
    }
    
    public function testToArray_returnsCorrectArray(): void
    {
        $resourceUsage = new ProcessResourceUsageVO(12.34, 56.78);
        
        $result = $resourceUsage->toArray();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('cpu', $result);
        $this->assertArrayHasKey('mem', $result);
        $this->assertSame(12.34, $result['cpu']);
        $this->assertSame(56.78, $result['mem']);
    }
} 