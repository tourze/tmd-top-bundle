<?php

namespace Tourze\TmdTopBundle\Tests\Unit\Adapter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Tourze\TmdTopBundle\Adapter\AdapterInterface;
use Tourze\TmdTopBundle\VO\ConnectionInfoVO;
use Tourze\TmdTopBundle\VO\NetcardInfoVO;
use Tourze\TmdTopBundle\VO\ProcessInfoVO;
use Tourze\TmdTopBundle\VO\ProcessResourceUsageVO;
use Tourze\TmdTopBundle\VO\ServiceInfoVO;

/**
 * 用于测试的适配器实现
 */
class TestAdapter implements AdapterInterface
{
    /**
     * @var Collection<int, NetcardInfoVO>
     */
    private Collection $netcardInfo;
    
    /**
     * @var Collection<int, ServiceInfoVO>
     */
    private Collection $servicesInfo;
    
    /**
     * @var Collection<int, ConnectionInfoVO>
     */
    private Collection $connectionsInfo;
    
    /**
     * @var Collection<int, ProcessInfoVO>
     */
    private Collection $processesInfo;
    
    /**
     * @var array<string, ProcessResourceUsageVO>
     */
    private array $processResourceUsage = [];
    
    public function __construct()
    {
        $this->netcardInfo = new ArrayCollection();
        $this->servicesInfo = new ArrayCollection();
        $this->connectionsInfo = new ArrayCollection();
        $this->processesInfo = new ArrayCollection();
    }
    
    /**
     * 设置网卡信息测试数据
     * 
     * @param Collection<int, NetcardInfoVO> $netcardInfo
     */
    public function setNetcardInfo(Collection $netcardInfo): void
    {
        $this->netcardInfo = $netcardInfo;
    }
    
    /**
     * 设置服务信息测试数据
     * 
     * @param Collection<int, ServiceInfoVO> $servicesInfo
     */
    public function setServicesInfo(Collection $servicesInfo): void
    {
        $this->servicesInfo = $servicesInfo;
    }
    
    /**
     * 设置连接信息测试数据
     * 
     * @param Collection<int, ConnectionInfoVO> $connectionsInfo
     */
    public function setConnectionsInfo(Collection $connectionsInfo): void
    {
        $this->connectionsInfo = $connectionsInfo;
    }
    
    /**
     * 设置进程信息测试数据
     * 
     * @param Collection<int, ProcessInfoVO> $processesInfo
     */
    public function setProcessesInfo(Collection $processesInfo): void
    {
        $this->processesInfo = $processesInfo;
    }
    
    /**
     * 设置进程资源使用情况测试数据
     * 
     * @param string $pid
     * @param ProcessResourceUsageVO $usage
     */
    public function setProcessResourceUsage(string $pid, ProcessResourceUsageVO $usage): void
    {
        $this->processResourceUsage[$pid] = $usage;
    }
    
    /**
     * 获取网卡信息
     * 
     * @return Collection<int, NetcardInfoVO>
     */
    public function getNetcardInfo(): Collection
    {
        return $this->netcardInfo;
    }
    
    /**
     * 获取监听服务信息
     * 
     * @return Collection<int, ServiceInfoVO>
     */
    public function getServicesInfo(): Collection
    {
        return $this->servicesInfo;
    }
    
    /**
     * 获取连接信息
     * 
     * @return Collection<int, ConnectionInfoVO>
     */
    public function getConnectionsInfo(): Collection
    {
        return $this->connectionsInfo;
    }
    
    /**
     * 获取进程信息
     * 
     * @return Collection<int, ProcessInfoVO>
     */
    public function getProcessesInfo(): Collection
    {
        return $this->processesInfo;
    }
    
    /**
     * 获取进程的资源使用情况
     */
    public function getProcessResourceUsage(string $pid): ProcessResourceUsageVO
    {
        return $this->processResourceUsage[$pid] ?? new ProcessResourceUsageVO(0.0, 0.0);
    }
} 