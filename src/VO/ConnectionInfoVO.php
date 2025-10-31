<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\VO;

class ConnectionInfoVO
{
    public function __construct(
        private readonly string $remoteIp,
        private readonly string $remotePort,
        private readonly int $uploadBytes,
        private readonly int $downloadBytes,
        private readonly string $location,
    ) {
    }

    public function getRemoteIp(): string
    {
        return $this->remoteIp;
    }

    public function getRemotePort(): string
    {
        return $this->remotePort;
    }

    public function getUploadBytes(): int
    {
        return $this->uploadBytes;
    }

    public function getDownloadBytes(): int
    {
        return $this->downloadBytes;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * 将数组转换为VO对象
     *
     * @param array<int, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $remoteIp = $data[0] ?? '';
        $remotePort = $data[1] ?? '';
        $uploadBytes = $data[2] ?? 0;
        $downloadBytes = $data[3] ?? 0;
        $location = $data[4] ?? '未知';

        return new self(
            is_string($remoteIp) ? $remoteIp : '',
            is_string($remotePort) ? $remotePort : '',
            is_int($uploadBytes) ? $uploadBytes : 0,
            is_int($downloadBytes) ? $downloadBytes : 0,
            is_string($location) ? $location : '未知'
        );
    }

    /**
     * 将VO对象转换为数组表示形式
     *
     * @return array{0: string, 1: string, 2: int, 3: int, 4: string}
     */
    public function toArray(): array
    {
        return [
            $this->remoteIp,
            $this->remotePort,
            $this->uploadBytes,
            $this->downloadBytes,
            $this->location,
        ];
    }
}
