<?php

namespace Tourze\TmdTopBundle\Tests\Unit\Service;

use Doctrine\Common\Collections\Collection;
use Tourze\TmdTopBundle\Adapter\AdapterInterface;
use Tourze\TmdTopBundle\Service\NetworkMonitor;
use Tourze\TmdTopBundle\VO\ConnectionInfoVO;
use Tourze\TmdTopBundle\VO\NetcardInfoVO;
use Tourze\TmdTopBundle\VO\ProcessInfoVO;
use Tourze\TmdTopBundle\VO\ProcessResourceUsageVO;
use Tourze\TmdTopBundle\VO\ServiceInfoVO;

/**
 * 可测试的NetworkMonitor子类，允许注入测试适配器
 */
class TestNetworkMonitor extends NetworkMonitor
{
    /**
     * 存储测试适配器的引用
     */
    private AdapterInterface $testAdapter;
    
    /**
     * TestNetworkMonitor 构造函数
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->testAdapter = $adapter;
        // 不调用父类构造函数，避免创建默认适配器
    }
    
    /**
     * 覆盖父类方法，直接使用测试适配器
     *
     * @return Collection<int, NetcardInfoVO>
     */
    public function getNetcardInfo(): Collection
    {
        return $this->testAdapter->getNetcardInfo();
    }

    /**
     * 覆盖父类方法，直接使用测试适配器
     *
     * @return Collection<int, ServiceInfoVO>
     */
    public function getServicesInfo(): Collection
    {
        return $this->testAdapter->getServicesInfo();
    }

    /**
     * 覆盖父类方法，直接使用测试适配器
     *
     * @return Collection<int, ConnectionInfoVO>
     */
    public function getConnectionsInfo(): Collection
    {
        return $this->testAdapter->getConnectionsInfo();
    }

    /**
     * 覆盖父类方法，直接使用测试适配器
     *
     * @return Collection<int, ProcessInfoVO>
     */
    public function getProcessesInfo(): Collection
    {
        return $this->testAdapter->getProcessesInfo();
    }
    
    /**
     * 覆盖父类方法，直接使用测试适配器
     */
    public function getProcessResourceUsage(string $pid): ProcessResourceUsageVO
    {
        return $this->testAdapter->getProcessResourceUsage($pid);
    }
} 