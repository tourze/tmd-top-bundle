<?php

namespace Tourze\TmdTopBundle\VO;

class ConnectionInfoVO
{
    public function __construct(
        private readonly string $remoteIp,
        private readonly string $remotePort,
        private readonly int $uploadBytes,
        private readonly int $downloadBytes,
        private readonly string $location
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
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data[0] ?? ''),
            (string)($data[1] ?? ''),
            (int)($data[2] ?? 0),
            (int)($data[3] ?? 0),
            (string)($data[4] ?? '未知')
        );
    }
    
    /**
     * 将VO对象转换为数组表示形式
     */
    public function toArray(): array
    {
        return [
            $this->remoteIp,
            $this->remotePort,
            $this->uploadBytes,
            $this->downloadBytes,
            $this->location
        ];
    }
}
