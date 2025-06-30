<?php

namespace Tourze\TmdTopBundle\Tests\Unit\Adapter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Tourze\TmdTopBundle\Adapter\AbstractAdapter;
use Tourze\TmdTopBundle\VO\ProcessResourceUsageVO;

/**
 * 用于测试 AbstractAdapter 的具体实现
 */
class TestAbstractAdapter extends AbstractAdapter
{
    /**
     * 获取网卡信息
     *
     * @return Collection<int, \Tourze\TmdTopBundle\VO\NetcardInfoVO>
     */
    public function getNetcardInfo(): Collection
    {
        return new ArrayCollection();
    }

    /**
     * 获取监听服务信息
     *
     * @return Collection<int, \Tourze\TmdTopBundle\VO\ServiceInfoVO>
     */
    public function getServicesInfo(): Collection
    {
        return new ArrayCollection();
    }

    /**
     * 获取连接信息
     *
     * @return Collection<int, \Tourze\TmdTopBundle\VO\ConnectionInfoVO>
     */
    public function getConnectionsInfo(): Collection
    {
        return new ArrayCollection();
    }

    /**
     * 获取进程信息
     *
     * @return Collection<int, \Tourze\TmdTopBundle\VO\ProcessInfoVO>
     */
    public function getProcessesInfo(): Collection
    {
        return new ArrayCollection();
    }

    /**
     * 获取进程的资源使用情况
     */
    public function getProcessResourceUsage(string $pid): ProcessResourceUsageVO
    {
        return new ProcessResourceUsageVO(0.0, 0.0);
    }

    /**
     * 公开受保护的方法以便测试
     */
    public function publicIsPrivateIp(string $ip): bool
    {
        return $this->isPrivateIp($ip);
    }

    /**
     * 公开受保护的方法以便测试
     */
    public function publicFormatBytesToKB(int $bytes): string
    {
        return $this->formatBytesToKB($bytes);
    }

    /**
     * 公开受保护的方法以便测试
     */
    public function publicExecuteCommand(string $command): array
    {
        return $this->executeCommand($command);
    }
}