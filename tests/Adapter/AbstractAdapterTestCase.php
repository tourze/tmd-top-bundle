<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests\Adapter;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\Adapter\AdapterInterface;
use Tourze\TmdTopBundle\VO\ProcessResourceUsageVO;

/**
 * 适配器的抽象测试类，定义了对所有适配器的通用测试
 * 这是一个抽象测试基类，不直接测试任何具体的类
 */
#[CoversNothing]
abstract class AbstractAdapterTestCase extends TestCase
{
    /**
     * 返回要测试的适配器实例
     */
    abstract protected function getAdapter(): AdapterInterface;

    /**
     * 测试获取网卡信息方法返回正确的集合类型
     */
    public function testGetNetcardInfoReturnsCollection(): void
    {
        $adapter = $this->getAdapter();
        $result = $adapter->getNetcardInfo();

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * 测试获取服务信息方法返回正确的集合类型
     */
    public function testGetServicesInfoReturnsCollection(): void
    {
        $adapter = $this->getAdapter();
        $result = $adapter->getServicesInfo();

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * 测试获取连接信息方法返回正确的集合类型
     */
    public function testGetConnectionsInfoReturnsCollection(): void
    {
        $adapter = $this->getAdapter();
        $result = $adapter->getConnectionsInfo();

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * 测试获取进程信息方法返回正确的集合类型
     */
    public function testGetProcessesInfoReturnsCollection(): void
    {
        $adapter = $this->getAdapter();
        $result = $adapter->getProcessesInfo();

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * 测试获取进程资源使用情况方法返回正确的对象类型
     */
    public function testGetProcessResourceUsageReturnsProcessResourceUsageVO(): void
    {
        $adapter = $this->getAdapter();
        $result = $adapter->getProcessResourceUsage('test-pid');

        $this->assertInstanceOf(ProcessResourceUsageVO::class, $result);
    }
}
