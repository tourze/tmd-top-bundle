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
        private readonly int $uploadBytes,
        private readonly int $downloadBytes,
        private readonly float $cpuUsage,
        private readonly float $memoryUsage
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

    public function getUploadBytes(): int
    {
        return $this->uploadBytes;
    }

    public function getDownloadBytes(): int
    {
        return $this->downloadBytes;
    }

    public function getCpuUsage(): float
    {
        return $this->cpuUsage;
    }

    public function getMemoryUsage(): float
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
            (int)($data[6] ?? 0),
            (int)($data[7] ?? 0),
            (float)($data[8] ?? 0.0),
            (float)($data[9] ?? 0.0)
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
            $this->uploadBytes,
            $this->downloadBytes,
            $this->cpuUsage,
            $this->memoryUsage
        ];
    }
}
