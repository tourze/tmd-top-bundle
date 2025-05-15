<?php

namespace Tourze\TmdTopBundle\VO;

class NetcardInfoVO
{
    public function __construct(
        private readonly string $name,
        private readonly string $uploadRate,
        private readonly string $downloadRate
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUploadRate(): string
    {
        return $this->uploadRate;
    }

    public function getDownloadRate(): string
    {
        return $this->downloadRate;
    }
    
    /**
     * 将数组转换为VO对象
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data[0] ?? '',
            $data[1] ?? '0.00 KB',
            $data[2] ?? '0.00 KB'
        );
    }
    
    /**
     * 将VO对象转换为数组表示形式
     */
    public function toArray(): array
    {
        return [
            $this->name,
            $this->uploadRate,
            $this->downloadRate
        ];
    }
}
