<?php

namespace Tourze\TmdTopBundle\VO;

class ProcessInfoVO
{
    public function __construct(
        private readonly string $pid,
        private readonly string $name,
        private readonly int $ipCount,
        private readonly int $connectionCount,
        private readonly string $uploadRate,
        private readonly string $downloadRate,
        private readonly string $cpuUsage,
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
            (string)($data[4] ?? '0.00 KB'),
            (string)($data[5] ?? '0.00 KB'),
            (string)($data[6] ?? '0.0%'),
            (string)($data[7] ?? '0.0%')
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
            $this->uploadRate,
            $this->downloadRate,
            $this->cpuUsage,
            $this->region
        ];
    }
}
