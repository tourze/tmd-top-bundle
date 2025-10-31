<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\VO;

class NetcardInfoVO
{
    public function __construct(
        private readonly string $name,
        private readonly int $uploadBytes,
        private readonly int $downloadBytes,
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
     *
     * @param array<int, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $name = $data[0] ?? '';
        $uploadBytes = $data[1] ?? 0;
        $downloadBytes = $data[2] ?? 0;

        return new self(
            is_string($name) ? $name : '',
            is_int($uploadBytes) ? $uploadBytes : 0,
            is_int($downloadBytes) ? $downloadBytes : 0
        );
    }

    /**
     * 将VO对象转换为数组表示形式
     *
     * @return array{0: string, 1: int, 2: int}
     */
    public function toArray(): array
    {
        return [
            $this->name,
            $this->uploadBytes,
            $this->downloadBytes,
        ];
    }
}
