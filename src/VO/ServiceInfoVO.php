<?php

namespace Tourze\TmdTopBundle\VO;

class ServiceInfoVO
{
    public function __construct(
        private readonly string $pid,
        private readonly string $serviceName,
        private readonly string $ip,
        private readonly string $port,
        private readonly int $ipCount,
        private readonly int $connectionCount,
        private readonly string $uploadRate,
        private readonly string $downloadRate,
        private readonly string $cpuUsage,
        private readonly string $memoryUsage
    ) {
    }

    public function getPid(): string
    {
        return $this->pid;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getPort(): string
    {
        return $this->port;
    }

    public function getIpCount(): int
    {
        return $this->ipCount;
    }

    public function getConnectionCount(): int
    {
        return $this->connectionCount;
    }

    public function getUploadRate(): string
    {
        return $this->uploadRate;
    }

    public function getDownloadRate(): string
    {
        return $this->downloadRate;
    }

    public function getCpuUsage(): string
    {
        return $this->cpuUsage;
    }

    public function getMemoryUsage(): string
    {
        return $this->memoryUsage;
    }
    
    /**
     * 将数组转换为VO对象
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data[0] ?? ''),
            (string)($data[1] ?? ''),
            (string)($data[2] ?? ''),
            (string)($data[3] ?? ''),
            (int)($data[4] ?? 0),
            (int)($data[5] ?? 0),
            (string)($data[6] ?? '0.00 KB'),
            (string)($data[7] ?? '0.00 KB'),
            (string)($data[8] ?? '0.0%'),
            (string)($data[9] ?? '0.0%')
        );
    }
    
    /**
     * 将VO对象转换为数组表示形式
     */
    public function toArray(): array
    {
        return [
            $this->pid,
            $this->serviceName,
            $this->ip,
            $this->port,
            $this->ipCount,
            $this->connectionCount,
            $this->uploadRate,
            $this->downloadRate,
            $this->cpuUsage,
            $this->memoryUsage
        ];
    }
}
