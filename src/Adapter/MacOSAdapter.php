<?php

namespace Tourze\TmdTopBundle\Adapter;

class MacOSAdapter extends AbstractAdapter
{
    /**
     * 获取网卡信息
     */
    public function getNetcardInfo(): array
    {
        $result = [];
        
        // 使用netstat命令获取网络接口统计信息
        $netstatOutput = $this->executeCommand('netstat -ib');

        // 处理接口信息
        $interfaces = [];
        
        foreach ($netstatOutput as $line) {
            if (preg_match('/^(\w+)\s+\d+\s+<Link>\s+\d+\s+(\d+)\s+\d+\s+\d+\s+(\d+)/', $line, $matches)) {
                $ifname = $matches[1];
                $ibytes = (int)$matches[2]; // 输入字节
                $obytes = (int)$matches[3]; // 输出字节
                
                $interfaces[$ifname] = [
                    'bytes_sent' => $obytes,
                    'bytes_recv' => $ibytes,
                ];
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
        
        // 使用lsof命令获取监听服务信息
        $lsofOutput = $this->executeCommand('lsof -i -P -n | grep LISTEN');
        
        foreach ($lsofOutput as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) < 10) {
                continue;
            }

            $serviceName = $parts[0];
            $pid = $parts[1];
            
            // 解析IP和端口
            if (preg_match('/(.+):(\d+)/', $parts[8], $matches)) {
                $ip = $matches[1];
                if ($ip === '*') {
                    $ip = '*';
                }
                $port = $matches[2];
                
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
        }
        
        return $result;
    }

    /**
     * 获取连接信息
     */
    public function getConnectionsInfo(): array
    {
        $result = [];
        
        // 使用netstat获取已建立的连接
        $netstatOutput = $this->executeCommand('netstat -an | grep ESTABLISHED');
        
        foreach ($netstatOutput as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) < 5) {
                continue;
            }
            
            // 解析本地地址和远程地址
            $localAddr = $parts[3];
            $remoteAddr = $parts[4];
            
            if (preg_match('/(.+)\.(\d+)$/', $remoteAddr, $matches)) {
                $remoteIp = $matches[1];
                $remotePort = $matches[2];
                
                // 将点分隔的端口转为数字
                $remoteIp = str_replace('.', '', $remoteIp);
                $octets = [];
                for ($i = 0; $i < strlen($remoteIp); $i += 3) {
                    $octets[] = substr($remoteIp, $i, 3);
                }
                $remoteIp = implode('.', $octets);
                
                // 忽略本地回环地址
                if ($remoteIp === '127.0.0.1') {
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
        }
        
        return $result;
    }

    /**
     * 获取进程信息
     */
    public function getProcessesInfo(): array
    {
        $result = [];
        
        // 使用lsof命令获取使用网络的进程
        $lsofOutput = $this->executeCommand('lsof -i -P -n | grep ESTABLISHED');
        
        $processConnections = [];
        
        foreach ($lsofOutput as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) < 10) {
                continue;
            }
            
            $procName = $parts[0];
            $pid = $parts[1];
            
            if (!isset($processConnections[$pid])) {
                $processConnections[$pid] = [
                    'name' => $procName,
                    'ips' => [],
                    'connections' => 0,
                ];
            }
            
            // 解析远程IP
            if (preg_match('/(.+):(\d+)->(.+):(\d+)/', $parts[8], $ipMatches) || 
                preg_match('/(.+):(\d+)/', $parts[8], $ipMatches)) {
                
                $remoteIp = isset($ipMatches[3]) ? $ipMatches[3] : $ipMatches[1];
                // 处理IPv4地址，将点分隔的端口转为数字
                if (strpos($remoteIp, '.') !== false) {
                    $remoteIp = preg_replace('/\.(\d+)$/', '', $remoteIp);
                }
                
                if (!in_array($remoteIp, $processConnections[$pid]['ips'])) {
                    $processConnections[$pid]['ips'][] = $remoteIp;
                }
                $processConnections[$pid]['connections']++;
            }
        }
        
        // 获取进程资源使用情况并组装最终结果
        foreach ($processConnections as $pid => $info) {
            $cpuMem = $this->getProcessResourceUsage($pid);
            
            $result[] = [
                $pid,
                $info['name'],
                count($info['ips']),
                $info['connections'],
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
        
        // 使用ps命令获取进程资源使用情况
        $psOutput = $this->executeCommand("ps -o %cpu,%mem -p $pid");
        
        if (isset($psOutput[1])) {
            $parts = preg_split('/\s+/', trim($psOutput[1]));
            if (count($parts) >= 2) {
                $result['cpu'] = $parts[0];
                $result['mem'] = $parts[1];
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
        
        $lsofOutput = $this->executeCommand("lsof -i :$port | grep ESTABLISHED");
        $ips = [];
        
        foreach ($lsofOutput as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) < 10) {
                continue;
            }
            
            if (preg_match('/(.+):(\d+)->(.+):(\d+)/', $parts[8], $matches)) {
                $remoteIp = $matches[3];
                if (!in_array($remoteIp, $ips)) {
                    $ips[] = $remoteIp;
                    $result['ipCount']++;
                }
                $result['connCount']++;
            }
        }
        
        return $result;
    }
}
