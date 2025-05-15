<?php

namespace Tourze\TmdTopBundle\VO;

class ProcessResourceUsageVO
{
    public function __construct(
        private readonly string $cpu,
        private readonly string $mem
    ) {
    }

    public function getCpu(): string
    {
        return $this->cpu;
    }

    public function getMem(): string
    {
        return $this->mem;
    }
    
    /**
     * 将数组转换为VO对象
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data['cpu'] ?? '0.0'),
            (string)($data['mem'] ?? '0.0')
        );
    }
    
    /**
     * 将VO对象转换为数组表示形式
     */
    public function toArray(): array
    {
        return [
            'cpu' => $this->cpu,
            'mem' => $this->mem
        ];
    }
}
