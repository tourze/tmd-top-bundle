<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Adapter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Tourze\TmdTopBundle\VO\ConnectionInfoVO;
use Tourze\TmdTopBundle\VO\NetcardInfoVO;
use Tourze\TmdTopBundle\VO\ProcessInfoVO;
use Tourze\TmdTopBundle\VO\ProcessResourceUsageVO;
use Tourze\TmdTopBundle\VO\ServiceInfoVO;

class LinuxAdapter extends AbstractAdapter
{
    /**
     * 获取网卡信息
     *
     * @return Collection<int, NetcardInfoVO>
     */
    public function getNetcardInfo(): Collection
    {
        $collection = new ArrayCollection();
        $interfaces = glob('/sys/class/net/*');

        if (false === $interfaces || [] === $interfaces) {
            return $collection;
        }

        foreach ($interfaces as $interface) {
            $ifname = basename($interface);

            // 获取网卡发送和接收的字节数
            $txBytes = file_exists("{$interface}/statistics/tx_bytes") ?
                (int) file_get_contents("{$interface}/statistics/tx_bytes") : 0;
            $rxBytes = file_exists("{$interface}/statistics/rx_bytes") ?
                (int) file_get_contents("{$interface}/statistics/rx_bytes") : 0;

            $netcardInfo = new NetcardInfoVO($ifname, $txBytes, $rxBytes);
            $collection->add($netcardInfo);
        }

        return $collection;
    }

    /**
     * 获取监听服务信息
     *
     * @return Collection<int, ServiceInfoVO>
     */
    public function getServicesInfo(): Collection
    {
        $collection = new ArrayCollection();

        // 获取监听的端口和服务
        $netstatOutput = $this->executeCommand('netstat -tulpn | grep LISTEN');

        foreach ($netstatOutput as $line) {
            // 解析netstat输出获取服务信息
            preg_match('/\s+(\S+)\s+(\S+)\s+(\S+):(\S+)\s+.*?(\d+)\/(\S+)/', $line, $matches);
            if (!isset($matches[5]) || !isset($matches[6])) {
                continue;
            }

            $pid = $matches[5];
            $serviceName = $matches[6];
            $ip = '*' === $matches[3] ? '*' : $matches[3];
            $port = $matches[4];

            // 获取连接数和IP数
            $connectionInfo = $this->getConnectionInfo($port);

            // 获取CPU和内存使用情况
            $resourceUsage = $this->getProcessResourceUsage($pid);

            $serviceInfo = new ServiceInfoVO(
                $pid,
                $serviceName,
                $ip,
                $port,
                $connectionInfo['ipCount'],
                $connectionInfo['connCount'],
                0, // 上传字节数，实际实现需要监控一段时间
                0, // 下载字节数，实际实现需要监控一段时间
                $resourceUsage->getCpu(),
                $resourceUsage->getMem()
            );

            $collection->add($serviceInfo);
        }

        return $collection;
    }

    /**
     * 获取连接信息
     *
     * @return Collection<int, ConnectionInfoVO>
     */
    public function getConnectionsInfo(): Collection
    {
        $collection = new ArrayCollection();

        // 获取所有ESTABLISHED连接
        $netstatOutput = $this->executeCommand('netstat -tn | grep ESTABLISHED');

        foreach ($netstatOutput as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (!is_array($parts) || count($parts) < 5) {
                continue;
            }

            // 解析本地地址和远程地址
            $remoteAddress = explode(':', $parts[4]);
            $remoteIp = $remoteAddress[0] ?? '';
            $remotePort = $remoteAddress[1] ?? '';

            // 忽略本地回环地址
            if ('127.0.0.1' === $remoteIp) {
                continue;
            }

            $connectionInfo = new ConnectionInfoVO(
                $remoteIp,
                $remotePort,
                1024, // 上传字节数，实际实现需要监控一段时间
                1024, // 下载字节数，实际实现需要监控一段时间
                '未知' // 地理位置需要通过GeoIP服务获取
            );

            $collection->add($connectionInfo);
        }

        return $collection;
    }

    /**
     * 获取进程信息
     *
     * @return Collection<int, ProcessInfoVO>
     */
    public function getProcessesInfo(): Collection
    {
        $collection = new ArrayCollection();
        $ssOutput = $this->executeCommand('ss -tunp | grep ESTAB');
        $processConnections = $this->parseProcessConnections($ssOutput);

        foreach ($processConnections as $pid => $info) {
            $processInfo = $this->createProcessInfoVO((string) $pid, $info);
            $collection->add($processInfo);
        }

        return $collection;
    }

    /**
     * 解析进程连接信息
     *
     * @param array<string> $ssOutput
     * @return array<string, array{name: string, ips: array<string>, connections: int}>
     */
    private function parseProcessConnections(array $ssOutput): array
    {
        $processConnections = [];

        foreach ($ssOutput as $line) {
            $processInfo = $this->extractProcessInfoFromLine($line);
            if (null === $processInfo) {
                continue;
            }

            $pid = $processInfo['pid'];
            if (!isset($processConnections[$pid])) {
                $processConnections[$pid] = [
                    'name' => $processInfo['name'],
                    'ips' => [],
                    'connections' => 0,
                ];
            }

            $processConnections[$pid] = $this->updateProcessConnectionData($processConnections[$pid], $line);
        }

        return $processConnections;
    }

    /**
     * 从命令行中提取进程信息
     *
     * @return array{pid: string, name: string}|null
     */
    private function extractProcessInfoFromLine(string $line): ?array
    {
        if (1 === preg_match('/pid=(\d+),.*?\("([^"]+)"/', $line, $matches)) {
            return [
                'pid' => $matches[1],
                'name' => $matches[2],
            ];
        }

        return null;
    }

    /**
     * 更新进程连接数据
     *
     * @param array{name: string, ips: array<string>, connections: int} $processData
     * @return array{name: string, ips: array<string>, connections: int}
     */
    private function updateProcessConnectionData(array $processData, string $line): array
    {
        if (1 === preg_match('/ESTAB\s+\d+\s+\d+\s+\S+\s+(\S+):/', $line, $ipMatches)) {
            $remoteIp = explode(':', $ipMatches[1])[0];
            if (!in_array($remoteIp, $processData['ips'], true)) {
                $processData['ips'][] = $remoteIp;
            }
            ++$processData['connections'];
        }

        return $processData;
    }

    /**
     * 创建 ProcessInfoVO 对象
     *
     * @param array{name: string, ips: array<string>, connections: int} $info
     */
    private function createProcessInfoVO(string $pid, array $info): ProcessInfoVO
    {
        $resourceUsage = $this->getProcessResourceUsage($pid);

        return new ProcessInfoVO(
            $pid,
            $info['name'],
            count($info['ips']),
            $info['connections'],
            1024, // 上传字节数，实际实现需要监控一段时间
            0, // 下载字节数，实际实现需要监控一段时间
            $resourceUsage->getCpu(),
            '其他' // 实际应用可能是地区信息
        );
    }

    /**
     * 获取进程的资源使用情况
     */
    public function getProcessResourceUsage(string $pid): ProcessResourceUsageVO
    {
        $cpu = 0.0;
        $mem = 0.0;

        $psOutput = $this->executeCommand("ps -p {$pid} -o %cpu,%mem");

        if (isset($psOutput[1])) {
            $parts = preg_split('/\s+/', trim($psOutput[1]));
            if (is_array($parts) && count($parts) >= 2) {
                $cpu = (float) $parts[0];
                $mem = (float) $parts[1];
            }
        }

        return new ProcessResourceUsageVO($cpu, $mem);
    }

    /**
     * 获取连接信息
     *
     * @return array{ipCount: int, connCount: int}
     */
    private function getConnectionInfo(string $port): array
    {
        $result = [
            'ipCount' => 0,
            'connCount' => 0,
        ];

        $connCmd = "netstat -an | grep :{$port} | grep -v LISTEN | awk '{print \$5}' | cut -d: -f1 | sort | uniq -c";
        $connOutput = $this->executeCommand($connCmd);

        foreach ($connOutput as $connLine) {
            preg_match('/\s+(\d+)\s+(\S+)/', $connLine, $connMatches);
            if (isset($connMatches[1], $connMatches[2])) {
                ++$result['ipCount'];
                $result['connCount'] += (int) $connMatches[1];
            }
        }

        return $result;
    }
}
