<?php

namespace Tourze\TmdTopBundle\Adapter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Tourze\TmdTopBundle\VO\ConnectionInfoVO;
use Tourze\TmdTopBundle\VO\NetcardInfoVO;
use Tourze\TmdTopBundle\VO\ProcessInfoVO;
use Tourze\TmdTopBundle\VO\ProcessResourceUsageVO;
use Tourze\TmdTopBundle\VO\ServiceInfoVO;

class WindowsAdapter extends AbstractAdapter
{
    /**
     * 获取网卡信息
     *
     * @return Collection<int, NetcardInfoVO>
     */
    public function getNetcardInfo(): Collection
    {
        $collection = new ArrayCollection();

        // 使用PowerShell获取网卡信息
        $command = 'powershell.exe "Get-NetAdapter | Select-Object Name, @{Name=\'TxBytes\';Expression={(Get-NetAdapterStatistics -Name $_.Name).SentBytes}}, @{Name=\'RxBytes\';Expression={(Get-NetAdapterStatistics -Name $_.Name).ReceivedBytes}} | ConvertTo-Csv -NoTypeInformation"';
        $output = $this->executeCommand($command);

        // 跳过CSV头行
        if (count($output) > 1) {
            for ($i = 1; $i < count($output); $i++) {
                $row = str_getcsv($output[$i]);
                if (count($row) >= 3) {
                    $name = $row[0];
                    $txBytes = (int)$row[1];
                    $rxBytes = (int)$row[2];

                    $netcardInfo = new NetcardInfoVO($name, $txBytes, $rxBytes);
                    $collection->add($netcardInfo);
                }
            }
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

        // 使用netstat获取监听端口信息
        $command = 'netstat -ano | findstr LISTENING';
        $output = $this->executeCommand($command);

        $servicesInfo = [];

        foreach ($output as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) < 5) {
                continue;
            }

            [$proto, $localAddr, $foreignAddr, $state, $pid] = $parts;

            // 解析IP和端口
            [$ip, $port] = explode(':', $localAddr);
            $ip = $ip === '0.0.0.0' ? '*' : $ip;

            // 使用tasklist获取进程名称
            $processInfo = $this->getProcessNameByPid($pid);
            $serviceName = $processInfo['name'] ?? 'unknown';

            // 计算IP数和连接数
            $ipCount = 0;
            $connCount = 0;
            $connectionInfo = $this->getConnectionInfoByPort($port);
            $ipCount = $connectionInfo['ipCount'];
            $connCount = $connectionInfo['connCount'];

            // 获取进程的CPU和内存使用率
            $resourceUsage = $this->getProcessResourceUsage($pid);

            $serviceInfo = new ServiceInfoVO(
                $pid,
                $serviceName,
                $ip,
                $port,
                $ipCount,
                $connCount,
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

        // 使用netstat获取已建立的连接
        $command = 'netstat -ano | findstr ESTABLISHED';
        $output = $this->executeCommand($command);

        foreach ($output as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) < 5) {
                continue;
            }

            [$proto, $localAddr, $foreignAddr, $state, $pid] = $parts;

            // 解析远程IP和端口
            [$remoteIp, $remotePort] = explode(':', $foreignAddr);

            // 排除本地连接
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

        // 获取所有有网络连接的进程列表
        $command = 'netstat -ano | findstr ESTABLISHED';
        $output = $this->executeCommand($command);

        $processConnections = [];

        foreach ($output as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) < 5) {
                continue;
            }

            [$proto, $localAddr, $foreignAddr, $state, $pid] = $parts;

            // 解析远程IP
            [$remoteIp, $remotePort] = explode(':', $foreignAddr);

            if (!isset($processConnections[$pid])) {
                $processInfo = $this->getProcessNameByPid($pid);
                $processConnections[$pid] = [
                    'name' => $processInfo['name'] ?? 'unknown',
                    'ips' => [],
                    'connections' => 0,
                ];
            }

            if (!in_array($remoteIp, $processConnections[$pid]['ips'])) {
                $processConnections[$pid]['ips'][] = $remoteIp;
            }
            $processConnections[$pid]['connections']++;
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

        // 使用 PowerShell 获取进程 CPU 和内存使用率
        $command = "powershell.exe \"Get-Process -Id $pid | Select-Object CPU, @{Name='WorkingSetMB';Expression={\$_.WorkingSet/1MB}} | ConvertTo-Csv -NoTypeInformation\"";
        $output = $this->executeCommand($command);

        if (count($output) > 1) {
            $row = str_getcsv($output[1]);
            if (count($row) >= 2) {
                $cpu = (float)$row[0] / 100; // 转换为百分比
                $totalMem = $this->getTotalMemory();
                $usedMem = (float)$row[1];
                $mem = $totalMem > 0 ? ($usedMem / $totalMem) * 100 : 0;
            }
        }

        return new ProcessResourceUsageVO($cpu, $mem);
    }

    /**
     * 通过PID获取进程名称
     */
    private function getProcessNameByPid(string $pid): array
    {
        $command = "tasklist /FI \"PID eq $pid\" /FO CSV /NH";
        $output = $this->executeCommand($command);

        $result = [
            'name' => 'unknown',
            'memUsage' => '0',
        ];

        if (count($output) > 0) {
            $row = str_getcsv($output[0]);
            if (count($row) >= 5) {
                $result['name'] = $row[0];
                $result['memUsage'] = $row[4];
            }
        }

        return $result;
    }

    /**
     * 获取指定端口的连接信息
     */
    private function getConnectionInfoByPort(string $port): array
    {
        $command = "netstat -ano | findstr :$port | findstr ESTABLISHED";
        $output = $this->executeCommand($command);

        $result = [
            'ipCount' => 0,
            'connCount' => 0,
        ];

        $uniqueIps = [];
        foreach ($output as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 5) {
                [$proto, $localAddr, $foreignAddr, $state, $pid] = $parts;
                [$remoteIp, $remotePort] = explode(':', $foreignAddr);

                if (!in_array($remoteIp, $uniqueIps)) {
                    $uniqueIps[] = $remoteIp;
                }
                $result['connCount']++;
            }
        }

        $result['ipCount'] = count($uniqueIps);
        return $result;
    }

    /**
     * 获取系统总内存(MB)
     */
    private function getTotalMemory(): float
    {
        $command = 'powershell.exe "(Get-CimInstance Win32_ComputerSystem).TotalPhysicalMemory/1MB"';
        $output = $this->executeCommand($command);

        return count($output) > 0 ? (float)$output[0] : 0;
    }
}
