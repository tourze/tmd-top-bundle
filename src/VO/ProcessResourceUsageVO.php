<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\VO;

class ProcessResourceUsageVO
{
    public function __construct(
        private readonly float $cpu,
        private readonly float $mem,
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
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $cpu = $data['cpu'] ?? 0.0;
        $mem = $data['mem'] ?? 0.0;

        return new self(
            is_float($cpu) || is_int($cpu) || (is_string($cpu) && is_numeric($cpu)) ? (float) $cpu : 0.0,
            is_float($mem) || is_int($mem) || (is_string($mem) && is_numeric($mem)) ? (float) $mem : 0.0
        );
    }

    /**
     * 将VO对象转换为数组表示形式
     *
     * @return array{cpu: float, mem: float}
     */
    public function toArray(): array
    {
        return [
            'cpu' => $this->cpu,
            'mem' => $this->mem,
        ];
    }
}
