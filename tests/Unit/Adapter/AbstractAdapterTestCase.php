<?php

namespace Tourze\TmdTopBundle\Tests\Unit\Adapter;

use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\Adapter\AdapterInterface;

/**
 * 适配器的抽象测试类，定义了对所有适配器的通用测试
 */
abstract class AbstractAdapterTestCase extends TestCase
{
    /**
     * 返回要测试的适配器实例
     */
    abstract protected function getAdapter(): AdapterInterface;
    
    /**
     * 测试获取网卡信息方法返回正确的集合类型
     */
    public function testGetNetcardInfo_returnsCollection(): void
    {
        $adapter = $this->getAdapter();
        $result = $adapter->getNetcardInfo();
        
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $result);
    }
    
    /**
     * 测试获取服务信息方法返回正确的集合类型
     */
    public function testGetServicesInfo_returnsCollection(): void
    {
        $adapter = $this->getAdapter();
        $result = $adapter->getServicesInfo();
        
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $result);
    }
    
    /**
     * 测试获取连接信息方法返回正确的集合类型
     */
    public function testGetConnectionsInfo_returnsCollection(): void
    {
        $adapter = $this->getAdapter();
        $result = $adapter->getConnectionsInfo();
        
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $result);
    }
    
    /**
     * 测试获取进程信息方法返回正确的集合类型
     */
    public function testGetProcessesInfo_returnsCollection(): void
    {
        $adapter = $this->getAdapter();
        $result = $adapter->getProcessesInfo();
        
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $result);
    }
    
    /**
     * 测试获取进程资源使用情况方法返回正确的对象类型
     */
    public function testGetProcessResourceUsage_returnsProcessResourceUsageVO(): void
    {
        $adapter = $this->getAdapter();
        $result = $adapter->getProcessResourceUsage('test-pid');
        
        $this->assertInstanceOf(\Tourze\TmdTopBundle\VO\ProcessResourceUsageVO::class, $result);
    }
} 