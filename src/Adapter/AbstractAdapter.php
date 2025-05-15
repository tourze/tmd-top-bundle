<?php

namespace Tourze\TmdTopBundle\Adapter;

abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * 检查IP是否为私有IP
     */
    protected function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    /**
     * 格式化字节大小为KB
     */
    protected function formatBytesToKB(int $bytes): string
    {
        return round($bytes / 1024, 2) . ' KB';
    }

    /**
     * 执行命令并返回结果
     */
    protected function executeCommand(string $command): array
    {
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>/dev/null', $output, $returnCode);
        
        return $returnCode === 0 ? $output : [];
    }
}
