<?php

namespace Tourze\TmdTopBundle\Adapter;

use Doctrine\Common\Collections\Collection;
use Tourze\TmdTopBundle\VO\ConnectionInfoVO;
use Tourze\TmdTopBundle\VO\NetcardInfoVO;
use Tourze\TmdTopBundle\VO\ProcessInfoVO;
use Tourze\TmdTopBundle\VO\ProcessResourceUsageVO;
use Tourze\TmdTopBundle\VO\ServiceInfoVO;

interface AdapterInterface
{
    /**
     * 获取网卡信息
     * 
     * @return Collection<int, NetcardInfoVO>
     */
    public function getNetcardInfo(): Collection;

    /**
     * 获取监听服务信息
     * 
     * @return Collection<int, ServiceInfoVO>
     */
    public function getServicesInfo(): Collection;

    /**
     * 获取连接信息
     * 
     * @return Collection<int, ConnectionInfoVO>
     */
    public function getConnectionsInfo(): Collection;

    /**
     * 获取进程信息
     * 
     * @return Collection<int, ProcessInfoVO>
     */
    public function getProcessesInfo(): Collection;

    /**
     * 获取进程的资源使用情况
     */
    public function getProcessResourceUsage(string $pid): ProcessResourceUsageVO;
}
