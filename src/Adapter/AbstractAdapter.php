<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Adapter;

use Tourze\TmdTopBundle\VO\ConnectionInfoVO;
use Tourze\TmdTopBundle\VO\ProcessInfoVO;
use Tourze\TmdTopBundle\VO\ProcessResourceUsageVO;
use Tourze\TmdTopBundle\VO\ServiceInfoVO;

abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * 检查IP是否为私有IP
     */
    protected function isPrivateIp(string $ip): bool
    {
        return false === filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
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
     *
     * @return array<string>
     */
    protected function executeCommand(string $command): array
    {
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>/dev/null', $output, $returnCode);

        return 0 === $returnCode ? $output : [];
    }

    /**
     * 构建ServiceInfoVO实例
     *
     * @param array{pid: string, ip: string, port: string} $serviceData
     * @param array{ipCount: int, connCount: int} $connectionInfo
     */
    protected function buildServiceInfoVO(array $serviceData, string $serviceName, array $connectionInfo, ProcessResourceUsageVO $resourceUsage): ServiceInfoVO
    {
        return new ServiceInfoVO(
            $serviceData['pid'],
            $serviceName,
            $serviceData['ip'],
            $serviceData['port'],
            $connectionInfo['ipCount'],
            $connectionInfo['connCount'],
            0,
            0,
            $resourceUsage->getCpu(),
            $resourceUsage->getMem()
        );
    }

    /**
     * 构建ConnectionInfoVO实例
     *
     * @param array{remoteIp: string, remotePort: string} $connectionData
     */
    protected function buildConnectionInfoVO(array $connectionData): ConnectionInfoVO
    {
        return new ConnectionInfoVO(
            $connectionData['remoteIp'],
            $connectionData['remotePort'],
            1024,
            1024,
            '未知'
        );
    }

    /**
     * 构建ProcessInfoVO实例
     *
     * @param array{name: string, ips: array<int, string>, connections: int} $info
     */
    protected function buildProcessInfoVO(string $pid, array $info, ProcessResourceUsageVO $resourceUsage): ProcessInfoVO
    {
        return new ProcessInfoVO(
            $pid,
            $info['name'],
            count($info['ips']),
            $info['connections'],
            1024,
            0,
            $resourceUsage->getCpu(),
            '其他'
        );
    }

    /**
     * 检查PID是否有效
     */
    protected function isValidPid(string $pid): bool
    {
        return is_numeric($pid) && (int) $pid <= 100000;
    }

    /**
     * 检查是否是回环接口
     */
    protected function isLoopbackInterface(string $interfaceName): bool
    {
        return 'lo' === $interfaceName || 'lo0' === $interfaceName;
    }

    /**
     * 提取接口名称
     */
    protected function extractInterfaceName(string $line): ?string
    {
        if (1 === preg_match('/^([a-zA-Z0-9]+):/', $line, $matches) || 1 === preg_match('/^([a-zA-Z0-9]+)\s/', $line, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * 获取进程资源数据
     *
     * @return array{cpu: float, mem: float}
     */
    protected function fetchProcessResourceData(string $pid): array
    {
        try {
            $command = "ps -o %cpu,%mem -p {$pid} 2>/dev/null | tail -1";
            $output = array_values($this->executeCommand($command));

            if (count($output) > 0) {
                $parts = preg_split('/\s+/', trim($output[0]));
                if (is_array($parts) && count($parts) >= 2) {
                    return ['cpu' => (float) $parts[0], 'mem' => (float) $parts[1]];
                }
            }
        } catch (\Throwable $e) {
            // 捕获任何异常，返回默认值
        }

        return ['cpu' => 0.0, 'mem' => 0.0];
    }

    /**
     * 获取进程名称
     */
    protected function fetchProcessName(string $pid): string
    {
        try {
            $command = "ps -p {$pid} -o comm= 2>/dev/null";
            $output = array_values($this->executeCommand($command));

            return count($output) > 0 ? trim($output[0]) : 'unknown';
        } catch (\Throwable $e) {
            return 'unknown';
        }
    }
}
