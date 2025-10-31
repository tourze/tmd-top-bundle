<?php

declare(strict_types=1);

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
        private readonly string $region,
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
     *
     * @param array<int, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $pid = $data[0] ?? '';
        $name = $data[1] ?? '';
        $ipCount = $data[2] ?? 0;
        $connectionCount = $data[3] ?? 0;
        $uploadBytes = $data[4] ?? 0;
        $downloadBytes = $data[5] ?? 0;
        $cpuUsage = $data[6] ?? 0.0;
        $region = $data[7] ?? '';

        return new self(
            is_string($pid) ? $pid : '',
            is_string($name) ? $name : '',
            is_int($ipCount) ? $ipCount : 0,
            is_int($connectionCount) ? $connectionCount : 0,
            is_int($uploadBytes) ? $uploadBytes : 0,
            is_int($downloadBytes) ? $downloadBytes : 0,
            is_float($cpuUsage) || is_int($cpuUsage) ? (float) $cpuUsage : 0.0,
            is_string($region) ? $region : ''
        );
    }

    /**
     * 将VO对象转换为数组表示形式
     *
     * @return array{0: string, 1: string, 2: int, 3: int, 4: int, 5: int, 6: float, 7: string}
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
            $this->region,
        ];
    }
}
