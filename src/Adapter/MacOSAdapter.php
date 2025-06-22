<?php

namespace Tourze\TmdTopBundle\Adapter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Tourze\TmdTopBundle\VO\ConnectionInfoVO;
use Tourze\TmdTopBundle\VO\NetcardInfoVO;
use Tourze\TmdTopBundle\VO\ProcessInfoVO;
use Tourze\TmdTopBundle\VO\ProcessResourceUsageVO;
use Tourze\TmdTopBundle\VO\ServiceInfoVO;

class MacOSAdapter extends AbstractAdapter
{
    /**
     * 获取网卡信息
     *
     * @return Collection<int, NetcardInfoVO>
     */
    public function getNetcardInfo(): Collection
    {
        $collection = new ArrayCollection();

        // 使用ifconfig命令获取网卡信息而不是netstat
        $command = 'ifconfig';
        $output = $this->executeCommand($command);

        $interfaces = [];
        $currentInterface = null;

        foreach ($output as $line) {
            // 新网卡信息的开始行
            if (preg_match('/^([a-zA-Z0-9]+):/', $line, $matches) || preg_match('/^([a-zA-Z0-9]+)\s/', $line, $matches)) {
                $currentInterface = $matches[1];
                // 排除loopback接口
                if ($currentInterface === 'lo' || $currentInterface === 'lo0') {
                    $currentInterface = null;
                    continue;
                }
                if (!isset($interfaces[$currentInterface])) {
                    $interfaces[$currentInterface] = [
                        'tx' => 0,
                        'rx' => 0,
                    ];
                }
            } elseif ($currentInterface !== null && strpos($line, 'bytes') !== false) {
                // 查找包含接收(RX)和发送(TX)字节数的行
                if (preg_match('/RX packets \d+\s+bytes (\d+)/', $line, $matches)) {
                    $interfaces[$currentInterface]['rx'] = (int) $matches[1];
                }
                if (preg_match('/TX packets \d+\s+bytes (\d+)/', $line, $matches)) {
                    $interfaces[$currentInterface]['tx'] = (int) $matches[1];
                }
            }
        }

        // 如果仍然没有数据，尝试使用另一种格式解析
        if (empty($interfaces)) {
            foreach ($output as $line) {
                if (preg_match('/^([a-zA-Z0-9]+):/', $line, $matches)) {
                    $currentInterface = $matches[1];
                    if ($currentInterface !== 'lo' && $currentInterface !== 'lo0') {
                        $interfaces[$currentInterface] = ['tx' => 0, 'rx' => 0];
                    }
                }
            }
        }

        // 创建VO对象并添加到集合
        foreach ($interfaces as $name => $data) {
            $netcardInfo = new NetcardInfoVO($name, $data['tx'], $data['rx']);
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

        // 使用netstat命令获取监听端口信息
        $command = 'netstat -anv -p tcp | grep LISTEN';
        $output = $this->executeCommand($command);

        foreach ($output as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) < 9) {
                continue;
            }

            // 解析地址和端口
            $localAddr = $parts[3];
            if (!preg_match('/(.+)\.(\d+)$/', $localAddr, $matches)) {
                continue;
            }

            $ip = $matches[1] === '*' ? '*' : $matches[1];
            $port = $matches[2];
            $pid = $parts[8];

            // 使用ps命令获取进程名称
            $serviceName = $this->getProcessNameByPid($pid);

            // 获取连接数和IP数
            $connectionInfo = $this->getConnectionInfoByPort($port);

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

        // 获取已建立的连接
        $command = 'netstat -anv -p tcp | grep ESTABLISHED';
        $output = $this->executeCommand($command);

        foreach ($output as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) < 5) {
                continue;
            }

            // 解析远程地址和端口
            $foreignAddr = $parts[4];
            if (!preg_match('/(.+)\.(\d+)$/', $foreignAddr, $matches)) {
                continue;
            }

            $remoteIp = $matches[1];
            $remotePort = $matches[2];

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

        // 获取有网络连接的进程
        $command = 'netstat -anv -p tcp | grep ESTABLISHED';
        $output = $this->executeCommand($command);

        $processConnections = [];

        foreach ($output as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) < 9) {
                continue;
            }

            $pid = $parts[8];
            $foreignAddr = $parts[4];

            if (!preg_match('/(.+)\.(\d+)$/', $foreignAddr, $matches)) {
                continue;
            }

            $remoteIp = $matches[1];

            if (!isset($processConnections[$pid])) {
                $processConnections[$pid] = [
                    'name' => $this->getProcessNameByPid($pid),
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

        // 检查PID是否为有效数字且在合理范围内
        if (!is_numeric($pid) || (int)$pid > 100000) {
            return new ProcessResourceUsageVO($cpu, $mem);
        }

        try {
            // 使用ps命令获取CPU和内存使用情况
            $command = "ps -o %cpu,%mem -p $pid 2>/dev/null | tail -1";
            $output = $this->executeCommand($command);

            if (count($output) > 0) {
                $parts = preg_split('/\s+/', trim($output[0]));
                if (count($parts) >= 2) {
                    $cpu = (float)$parts[0];
                    $mem = (float)$parts[1];
                }
            }
        } catch (\Throwable $e) {
            // 捕获任何异常，返回默认值
        }

        return new ProcessResourceUsageVO($cpu, $mem);
    }

    /**
     * 通过PID获取进程名称
     */
    private function getProcessNameByPid(string $pid): string
    {
        // 检查PID是否为有效数字且在合理范围内
        if (!is_numeric($pid) || (int)$pid > 100000) {
            return 'unknown';
        }

        try {
            $command = "ps -p $pid -o comm= 2>/dev/null";
            $output = $this->executeCommand($command);

            return count($output) > 0 ? trim($output[0]) : 'unknown';
        } catch (\Throwable $e) {
            return 'unknown';
        }
    }

    /**
     * 获取指定端口的连接信息
     */
    private function getConnectionInfoByPort(string $port): array
    {
        $command = "netstat -anv | grep .$port | grep ESTABLISHED";
        $output = $this->executeCommand($command);

        $result = [
            'ipCount' => 0,
            'connCount' => 0,
        ];

        $uniqueIps = [];

        foreach ($output as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 5) {
                $foreignAddr = $parts[4];
                if (preg_match('/(.+)\.(\d+)$/', $foreignAddr, $matches)) {
                    $remoteIp = $matches[1];
                    if (!in_array($remoteIp, $uniqueIps)) {
                        $uniqueIps[] = $remoteIp;
                    }
                    $result['connCount']++;
                }
            }
        }

        $result['ipCount'] = count($uniqueIps);
        return $result;
    }
}
