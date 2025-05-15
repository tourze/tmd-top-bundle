<?php

namespace Tourze\TmdTopBundle\Adapter;

class WindowsAdapter extends AbstractAdapter
{
    /**
     * 获取网卡信息
     */
    public function getNetcardInfo(): array
    {
        $result = [];
        
        // 使用netstat命令获取网络接口统计信息
        $netstatOutput = $this->executeCommand('netstat -e');
        
        // 处理输出结果
        $interfaces = [];
        $currentInterface = null;
        
        foreach ($netstatOutput as $line) {
            if (strpos($line, 'Interface') === 0) {
                // 新网卡开始
                $currentInterface = trim(str_replace('Interface', '', $line));
                $interfaces[$currentInterface] = [
                    'bytes_sent' => 0,
                    'bytes_recv' => 0,
                ];
            } elseif ($currentInterface && preg_match('/^\s+Bytes\s+(\d+)\s+(\d+)/', $line, $matches)) {
                // 数据行
                $interfaces[$currentInterface]['bytes_sent'] = (int)$matches[1];
                $interfaces[$currentInterface]['bytes_recv'] = (int)$matches[2];
            }
        }
        
        // 如果netstat命令没有返回预期结果，尝试使用WMI
        if (empty($interfaces)) {
            $wmiOutput = $this->executeCommand('wmic NIC get Name, BytesSentPersec, BytesReceivedPersec /format:csv');
            
            foreach ($wmiOutput as $line) {
                $parts = str_getcsv($line);
                if (count($parts) >= 4 && is_numeric($parts[2]) && is_numeric($parts[3])) {
                    $interfaces[$parts[1]] = [
                        'bytes_sent' => (int)$parts[2],
                        'bytes_recv' => (int)$parts[3],
                    ];
                }
            }
        }
        
        // 格式化输出
        foreach ($interfaces as $name => $data) {
            $result[] = [
                $name,
                $this->formatBytesToKB($data['bytes_sent']),
                $this->formatBytesToKB($data['bytes_recv']),
            ];
        }
        
        return $result;
    }

    /**
     * 获取监听服务信息
     */
    public function getServicesInfo(): array
    {
        $result = [];
        
        // 使用netstat命令获取监听服务信息
        $netstatOutput = $this->executeCommand('netstat -ano | findstr LISTENING');
        
        // 创建临时表存储PID到进程名的映射
        $pidToName = $this->getPidToNameMapping();
        
        foreach ($netstatOutput as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) < 5) {
                continue;
            }
            
            $protocol = $parts[0];
            $localAddr = $parts[1];
            $pid = $parts[4];
            
            // 解析IP和端口
            if (!preg_match('/(.+):(\d+)$/', $localAddr, $matches)) {
                continue;
            }
            
            $ip = $matches[1] === '0.0.0.0' ? '*' : $matches[1];
            $port = $matches[2];
            $serviceName = $pidToName[$pid] ?? 'unknown';
            
            // 获取连接信息
            $connectionInfo = $this->getConnectionInfo($port);
            
            // 获取CPU和内存使用情况
            $cpuMem = $this->getProcessResourceUsage($pid);
            
            $result[] = [
                $pid,
                $serviceName,
                $ip,
                $port,
                $connectionInfo['ipCount'],
                $connectionInfo['connCount'],
                '0.00 KB', // 上传速率，实际实现需要监控一段时间
                '0.00 KB', // 下载速率，实际实现需要监控一段时间
                $cpuMem['cpu'] . '%',
                $cpuMem['mem'] . '%',
            ];
        }
        
        return $result;
    }

    /**
     * 获取连接信息
     */
    public function getConnectionsInfo(): array
    {
        $result = [];
        
        // 使用netstat命令获取已建立的连接
        $netstatOutput = $this->executeCommand('netstat -ano | findstr ESTABLISHED');
        
        foreach ($netstatOutput as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) < 5) {
                continue;
            }
            
            $remoteAddr = $parts[2];
            
            // 解析IP和端口
            if (!preg_match('/(.+):(\d+)$/', $remoteAddr, $matches)) {
                continue;
            }
            
            $remoteIp = $matches[1];
            $remotePort = $matches[2];
            
            // 忽略本地回环地址
            if ($remoteIp === '127.0.0.1' || $remoteIp === '::1') {
                continue;
            }
            
            $result[] = [
                $remoteIp,
                $remotePort,
                '1.00 KB', // 上传速率，实际实现需要监控一段时间
                '1.00 KB', // 下载速率，实际实现需要监控一段时间
                '未知', // 地理位置需要通过GeoIP服务获取
            ];
        }
        
        return $result;
    }

    /**
     * 获取进程信息
     */
    public function getProcessesInfo(): array
    {
        $result = [];
        
        // 获取所有进程信息
        $tasklist = $this->executeCommand('tasklist /FO CSV');
        
        // 获取正在使用网络的PID
        $netstatOutput = $this->executeCommand('netstat -ano | findstr ESTABLISHED');
        $networkPids = [];
        
        foreach ($netstatOutput as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 5) {
                $pid = $parts[4];
                if (!isset($networkPids[$pid])) {
                    $networkPids[$pid] = [
                        'ips' => [],
                        'connections' => 0,
                    ];
                }
                
                if (isset($parts[2]) && preg_match('/(.+):(\d+)$/', $parts[2], $matches)) {
                    $remoteIp = $matches[1];
                    if (!in_array($remoteIp, $networkPids[$pid]['ips'])) {
                        $networkPids[$pid]['ips'][] = $remoteIp;
                    }
                    $networkPids[$pid]['connections']++;
                }
            }
        }
        
        // 处理进程列表
        foreach ($tasklist as $line) {
            if (strpos($line, '"') !== 0) {
                continue;
            }
            
            $parts = str_getcsv($line);
            if (count($parts) < 5) {
                continue;
            }
            
            $name = $parts[0];
            $pid = str_replace('"', '', $parts[1]);
            
            // 只包含有网络连接的进程
            if (!isset($networkPids[$pid])) {
                continue;
            }
            
            // 获取CPU和内存使用情况
            $cpuMem = $this->getProcessResourceUsage($pid);
            
            $result[] = [
                $pid,
                $name,
                count($networkPids[$pid]['ips']),
                $networkPids[$pid]['connections'],
                '1.00 KB', // 上传速率，实际实现需要监控一段时间
                '0.00 KB', // 下载速率，实际实现需要监控一段时间
                $cpuMem['cpu'] . '%',
                '0.1%',
            ];
        }
        
        return $result;
    }

    /**
     * 获取进程的资源使用情况
     */
    public function getProcessResourceUsage(string $pid): array
    {
        $result = [
            'cpu' => '0.0',
            'mem' => '0.0',
        ];
        
        // 对于Windows，可以使用typeperf或wmic命令
        $wmicOutput = $this->executeCommand("wmic process where ProcessId=$pid get WorkingSetSize /format:csv");
        
        // 内存使用
        foreach ($wmicOutput as $line) {
            $parts = str_getcsv($line);
            if (count($parts) >= 2 && is_numeric($parts[1])) {
                // 转换为MB并计算百分比
                $totalMem = $this->getTotalMemory();
                $memBytes = (float)$parts[1];
                $memPercent = $totalMem > 0 ? (($memBytes / $totalMem) * 100) : 0;
                $result['mem'] = number_format($memPercent, 1);
                break;
            }
        }
        
        // CPU使用更复杂，这里提供一个简化的估算
        $result['cpu'] = '0.0'; // 在Windows中准确获取单个进程的CPU使用率需要更复杂的逻辑
        
        return $result;
    }
    
    /**
     * 获取PID到进程名称的映射
     */
    private function getPidToNameMapping(): array
    {
        $result = [];
        $tasklist = $this->executeCommand('tasklist /FO CSV');
        
        foreach ($tasklist as $line) {
            if (strpos($line, '"') !== 0) {
                continue;
            }
            
            $parts = str_getcsv($line);
            if (count($parts) >= 2) {
                $name = $parts[0];
                $pid = str_replace('"', '', $parts[1]);
                $result[$pid] = $name;
            }
        }
        
        return $result;
    }
    
    /**
     * 获取特定端口的连接信息
     */
    private function getConnectionInfo(string $port): array
    {
        $result = [
            'ipCount' => 0,
            'connCount' => 0,
        ];
        
        $netstatOutput = $this->executeCommand("netstat -ano | findstr :$port | findstr ESTABLISHED");
        $ips = [];
        
        foreach ($netstatOutput as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 3 && preg_match('/(.+):(\d+)$/', $parts[2], $matches)) {
                $remoteIp = $matches[1];
                if (!in_array($remoteIp, $ips)) {
                    $ips[] = $remoteIp;
                    $result['ipCount']++;
                }
                $result['connCount']++;
            }
        }
        
        return $result;
    }
    
    /**
     * 获取系统总内存（字节）
     */
    private function getTotalMemory(): float
    {
        $memory = 0;
        $wmicOutput = $this->executeCommand('wmic computersystem get TotalPhysicalMemory /format:csv');
        
        foreach ($wmicOutput as $line) {
            $parts = str_getcsv($line);
            if (count($parts) >= 2 && is_numeric($parts[1])) {
                $memory = (float)$parts[1];
                break;
            }
        }
        
        return $memory;
    }
}
