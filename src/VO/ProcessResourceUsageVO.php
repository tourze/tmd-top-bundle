<?php

namespace Tourze\TmdTopBundle\VO;

class ProcessResourceUsageVO
{
    public function __construct(
        private readonly float $cpu,
        private readonly float $mem
    ) {
    }

    public function getCpu(): float
    {
        return $this->cpu;
    }

    public function getMem(): float
    {
        return $this->mem;
    }
    
    /**
     * 将数组转换为VO对象
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (float)($data['cpu'] ?? 0.0),
            (float)($data['mem'] ?? 0.0)
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
