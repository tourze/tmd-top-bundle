<?php

namespace Tourze\TmdTopBundle\VO;

class NetcardInfoVO
{
    public function __construct(
        private readonly string $name,
        private readonly int $uploadBytes,
        private readonly int $downloadBytes
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUploadBytes(): int
    {
        return $this->uploadBytes;
    }

    public function getDownloadBytes(): int
    {
        return $this->downloadBytes;
    }
    
    /**
     * 将数组转换为VO对象
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data[0] ?? '',
            (int)($data[1] ?? 0),
            (int)($data[2] ?? 0)
        );
    }
    
    /**
     * 将VO对象转换为数组表示形式
     */
    public function toArray(): array
    {
        return [
            $this->name,
            $this->uploadBytes,
            $this->downloadBytes
        ];
    }
}
