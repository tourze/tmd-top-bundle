<?php

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

        if (empty($interfaces)) {
            return $collection;
        }

        foreach ($interfaces as $interface) {
            $ifname = basename($interface);

            // 获取网卡发送和接收的字节数
            $txBytes = file_exists("$interface/statistics/tx_bytes") ?
                (int)file_get_contents("$interface/statistics/tx_bytes") : 0;
            $rxBytes = file_exists("$interface/statistics/rx_bytes") ?
                (int)file_get_contents("$interface/statistics/rx_bytes") : 0;

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
            $ip = $matches[3] === '*' ? '*' : $matches[3];
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
            if (count($parts) < 5) {
                continue;
            }

            // 解析本地地址和远程地址
            [$remoteIp, $remotePort] = explode(':', $parts[4]);

            // 忽略本地回环地址
            if ($remoteIp === '127.0.0.1') {
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

        // 获取所有有网络连接的进程
        $ssOutput = $this->executeCommand('ss -tunp | grep ESTAB');

        $processConnections = [];

        foreach ($ssOutput as $line) {
            if (preg_match('/pid=(\d+),.*?\("([^"]+)"/', $line, $matches)) {
                $pid = $matches[1];
                $procName = $matches[2];

                if (!isset($processConnections[$pid])) {
                    $processConnections[$pid] = [
                        'name' => $procName,
                        'ips' => [],
                        'connections' => 0,
                    ];
                }

                // 解析远程IP
                if (preg_match('/ESTAB\s+\d+\s+\d+\s+\S+\s+(\S+):/', $line, $ipMatches)) {
                    $remoteIp = explode(':', $ipMatches[1])[0];
                    if (!in_array($remoteIp, $processConnections[$pid]['ips'])) {
                        $processConnections[$pid]['ips'][] = $remoteIp;
                    }
                    $processConnections[$pid]['connections']++;
                }
            }
        }

        // 获取进程资源使用情况并组装最终结果
        foreach ($processConnections as $pid => $info) {
            $resourceUsage = $this->getProcessResourceUsage($pid);

            $processInfo = new ProcessInfoVO(
                $pid,
                $info['name'],
                count($info['ips']),
                $info['connections'],
                1024, // 上传字节数，实际实现需要监控一段时间
                0, // 下载字节数，实际实现需要监控一段时间
                $resourceUsage->getCpu(),
                '其他' // 实际应用可能是地区信息
            );

            $collection->add($processInfo);
        }

        return $collection;
    }

    /**
     * 获取进程的资源使用情况
     */
    public function getProcessResourceUsage(string $pid): ProcessResourceUsageVO
    {
        $cpu = 0.0;
        $mem = 0.0;

        $psOutput = $this->executeCommand("ps -p $pid -o %cpu,%mem");

        if (isset($psOutput[1])) {
            $parts = preg_split('/\s+/', trim($psOutput[1]));
            if (count($parts) >= 2) {
                $cpu = (float)$parts[0];
                $mem = (float)$parts[1];
            }
        }

        return new ProcessResourceUsageVO($cpu, $mem);
    }

    /**
     * 获取连接信息
     */
    private function getConnectionInfo(string $port): array
    {
        $result = [
            'ipCount' => 0,
            'connCount' => 0,
        ];

        $connCmd = "netstat -an | grep :$port | grep -v LISTEN | awk '{print \$5}' | cut -d: -f1 | sort | uniq -c";
        $connOutput = $this->executeCommand($connCmd);

        foreach ($connOutput as $connLine) {
            preg_match('/\s+(\d+)\s+(\S+)/', $connLine, $connMatches);
            if (isset($connMatches[1]) && isset($connMatches[2])) {
                $result['ipCount']++;
                $result['connCount'] += (int)$connMatches[1];
            }
        }

        return $result;
    }
}
