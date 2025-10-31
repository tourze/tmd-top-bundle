<?php

declare(strict_types=1);

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
        private readonly float $memoryUsage,
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
     *
     * @param array<int, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $pid = $data[0] ?? '';
        $serviceName = $data[1] ?? '';
        $ip = $data[2] ?? '';
        $port = $data[3] ?? '';
        $ipCount = $data[4] ?? 0;
        $connectionCount = $data[5] ?? 0;
        $uploadBytes = $data[6] ?? 0;
        $downloadBytes = $data[7] ?? 0;
        $cpuUsage = $data[8] ?? 0.0;
        $memoryUsage = $data[9] ?? 0.0;

        return new self(
            is_string($pid) ? $pid : '',
            is_string($serviceName) ? $serviceName : '',
            is_string($ip) ? $ip : '',
            is_string($port) ? $port : '',
            is_int($ipCount) ? $ipCount : 0,
            is_int($connectionCount) ? $connectionCount : 0,
            is_int($uploadBytes) ? $uploadBytes : 0,
            is_int($downloadBytes) ? $downloadBytes : 0,
            is_float($cpuUsage) || is_int($cpuUsage) ? (float) $cpuUsage : 0.0,
            is_float($memoryUsage) || is_int($memoryUsage) ? (float) $memoryUsage : 0.0
        );
    }

    /**
     * 将VO对象转换为数组表示形式
     *
     * @return array{0: string, 1: string, 2: string, 3: string, 4: int, 5: int, 6: int, 7: int, 8: float, 9: float}
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
            $this->memoryUsage,
        ];
    }
}
