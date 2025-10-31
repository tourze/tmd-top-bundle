<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests\Service;

use Doctrine\Common\Collections\Collection;
use Tourze\TmdTopBundle\Adapter\AdapterInterface;
use Tourze\TmdTopBundle\Service\NetworkMonitorInterface;
use Tourze\TmdTopBundle\VO\ConnectionInfoVO;
use Tourze\TmdTopBundle\VO\NetcardInfoVO;
use Tourze\TmdTopBundle\VO\ProcessInfoVO;
use Tourze\TmdTopBundle\VO\ProcessResourceUsageVO;
use Tourze\TmdTopBundle\VO\ServiceInfoVO;

/**
 * 可测试的NetworkMonitor实现，使用组合模式替代继承
 */
class TestNetworkMonitor implements NetworkMonitorInterface
{
    /**
     * 存储测试适配器的引用
     */
    private readonly AdapterInterface $adapter;

    /**
     * TestNetworkMonitor 构造函数
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * 获取网卡信息
     *
     * @return Collection<int, NetcardInfoVO>
     */
    public function getNetcardInfo(): Collection
    {
        return $this->adapter->getNetcardInfo();
    }

    /**
     * 获取监听服务信息
     *
     * @return Collection<int, ServiceInfoVO>
     */
    public function getServicesInfo(): Collection
    {
        return $this->adapter->getServicesInfo();
    }

    /**
     * 获取连接信息
     *
     * @return Collection<int, ConnectionInfoVO>
     */
    public function getConnectionsInfo(): Collection
    {
        return $this->adapter->getConnectionsInfo();
    }

    /**
     * 获取进程信息
     *
     * @return Collection<int, ProcessInfoVO>
     */
    public function getProcessesInfo(): Collection
    {
        return $this->adapter->getProcessesInfo();
    }

    /**
     * 获取进程的资源使用情况
     */
    public function getProcessResourceUsage(string $pid): ProcessResourceUsageVO
    {
        return $this->adapter->getProcessResourceUsage($pid);
    }

    /**
     * 检查IP是否为私有IP
     */
    public function isPrivateIp(string $ip): bool
    {
        return false === filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
