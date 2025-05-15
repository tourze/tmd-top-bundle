<?php

namespace Tourze\TmdTopBundle\VO;

class ProcessInfoVO
{
    public function __construct(
        private readonly string $pid,
        private readonly string $name,
        private readonly int $ipCount,
        private readonly int $connectionCount,
        private readonly int $uploadBytes,
        private readonly int $downloadBytes,
        private readonly float $cpuUsage,
        private readonly string $region
    ) {
    }

    public function getPid(): string
    {
        return $this->pid;
    }

    public function getName(): string
    {
        return $this->name;
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

    public function getRegion(): string
    {
        return $this->region;
    }
    
    /**
     * 将数组转换为VO对象
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data[0] ?? ''),
            (string)($data[1] ?? ''),
            (int)($data[2] ?? 0),
            (int)($data[3] ?? 0),
            (int)($data[4] ?? 0),
            (int)($data[5] ?? 0),
            (float)($data[6] ?? 0.0),
            (string)($data[7] ?? '')
        );
    }
    
    /**
     * 将VO对象转换为数组表示形式
     */
    public function toArray(): array
    {
        return [
            $this->pid,
            $this->name,
            $this->ipCount,
            $this->connectionCount,
            $this->uploadBytes,
            $this->downloadBytes,
            $this->cpuUsage,
            $this->region
        ];
    }
}
