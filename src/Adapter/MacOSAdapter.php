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

/**
 * @phpstan-ignore-next-line symplify.cognitiveComplexity
 */
class MacOSAdapter extends AbstractAdapter
{
    /**
     * 获取网卡信息
     *
     * @return Collection<int, NetcardInfoVO>
     */
    public function getNetcardInfo(): Collection
    {
        $output = $this->getIfconfigOutput();
        $interfaces = $this->parseInterfacesData($output);

        return $this->createNetcardCollection($interfaces);
    }

    /**
     * 解析ifconfig输出的主要方法
     *
     * @param array<int, string> $output
     * @return array<string, array{tx: int, rx: int}>
     */
    private function parseIfconfigOutput(array $output): array
    {
        $interfaces = [];
        $currentInterface = null;

        foreach ($output as $line) {
            $result = $this->processInterfaceLine($line, $interfaces, $currentInterface);
            $interfaces = $result['interfaces'];
            $currentInterface = $result['currentInterface'];
        }

        return $interfaces;
    }

    /**
     * 检查是否是新网卡接口行
     *
     * @param array<string, array{tx: int, rx: int}> $interfaces
     * @return array{interfaces: array<string, array{tx: int, rx: int}>, currentInterface: string|null, found: bool}
     */
    private function isNewInterfaceLine(string $line, array $interfaces): array
    {
        $interfaceName = $this->extractInterfaceName($line);
        if (null === $interfaceName) {
            return ['interfaces' => $interfaces, 'currentInterface' => null, 'found' => false];
        }

        if ($this->isLoopbackInterface($interfaceName)) {
            return ['interfaces' => $interfaces, 'currentInterface' => null, 'found' => true];
        }

        return $this->addNewInterface($interfaces, $interfaceName);
    }

    /**
     * 解析流量数据
     *
     * @param array<string, array{tx: int, rx: int}> $interfaces
     * @return array<string, array{tx: int, rx: int}>
     */
    private function parseTrafficData(string $line, string $currentInterface, array $interfaces): array
    {
        // 确保接口存在且有完整的 tx 和 rx 键
        if (!isset($interfaces[$currentInterface])) {
            $interfaces[$currentInterface] = ['tx' => 0, 'rx' => 0];
        }

        $rxBytes = $this->extractRxBytes($line);
        if (null !== $rxBytes) {
            $interfaces[$currentInterface]['rx'] = $rxBytes;
        }

        $txBytes = $this->extractTxBytes($line);
        if (null !== $txBytes) {
            $interfaces[$currentInterface]['tx'] = $txBytes;
        }

        return $interfaces;
    }

    /**
     * 备用解析方法
     *
     * @param array<int, string> $output
     * @return array<string, array{tx: int, rx: int}>
     */
    private function parseIfconfigOutputFallback(array $output): array
    {
        $interfaces = [];

        foreach ($output as $line) {
            $interfaceName = $this->extractInterfaceName($line);
            if (null !== $interfaceName && !$this->isLoopbackInterface($interfaceName)) {
                $interfaces[$interfaceName] = ['tx' => 0, 'rx' => 0];
            }
        }

        return $interfaces;
    }

    /**
     * 创建网卡信息集合
     *
     * @param array<string, array{tx: int, rx: int}> $interfaces
     * @return Collection<int, NetcardInfoVO>
     */
    private function createNetcardCollection(array $interfaces): Collection
    {
        $collection = new ArrayCollection();

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
        $output = $this->getNetstatListenOutput();
        $services = $this->parseServicesFromOutput($output);

        return $this->buildServicesCollection($services);
    }

    /**
     * 获取连接信息
     *
     * @return Collection<int, ConnectionInfoVO>
     */
    public function getConnectionsInfo(): Collection
    {
        $output = $this->getNetstatEstablishedOutput();
        $connections = $this->parseConnectionsFromOutput($output);
        $filteredConnections = $this->filterLocalConnections($connections);

        return $this->buildConnectionsCollection($filteredConnections);
    }

    /**
     * 获取进程信息
     *
     * @return Collection<int, ProcessInfoVO>
     */
    public function getProcessesInfo(): Collection
    {
        $output = $this->getNetstatEstablishedOutput();
        $processConnections = $this->collectProcessConnections($output);

        return $this->buildProcessesCollection($processConnections);
    }

    /**
     * 获取进程的资源使用情况
     */
    public function getProcessResourceUsage(string $pid): ProcessResourceUsageVO
    {
        if (!$this->isValidPid($pid)) {
            return new ProcessResourceUsageVO(0.0, 0.0);
        }

        $resourceData = $this->fetchProcessResourceData($pid);

        return new ProcessResourceUsageVO($resourceData['cpu'], $resourceData['mem']);
    }

    /**
     * 通过PID获取进程名称
     */
    private function getProcessNameByPid(string $pid): string
    {
        if (!$this->isValidPid($pid)) {
            return 'unknown';
        }

        return $this->fetchProcessName($pid);
    }

    /**
     * 获取指定端口的连接信息
     *
     * @return array{ipCount: int, connCount: int}
     */
    private function getConnectionInfoByPort(string $port): array
    {
        $command = "netstat -anv | grep .{$port} | grep ESTABLISHED";
        $output = array_values($this->executeCommand($command));

        return $this->parseConnectionOutput($output);
    }

    /**
     * 解析连接输出
     *
     * @param array<int, string> $output
     * @return array{ipCount: int, connCount: int}
     */
    private function parseConnectionOutput(array $output): array
    {
        $uniqueIps = [];
        $connCount = 0;

        foreach ($output as $line) {
            $connectionData = $this->extractConnectionData($line);
            if (null !== $connectionData) {
                $result = $this->updateConnectionCounts($connectionData, $uniqueIps, $connCount);
                $uniqueIps = $result['uniqueIps'];
                $connCount = $result['connCount'];
            }
        }

        return ['ipCount' => count($uniqueIps), 'connCount' => $connCount];
    }

    /**
     * 从行中提取连接数据
     */
    private function extractConnectionData(string $line): ?string
    {
        $parts = preg_split('/\s+/', trim($line));
        if (!is_array($parts) || count($parts) < 5) {
            return null;
        }

        $foreignAddr = $parts[4];
        if (1 !== preg_match('/(.+)\.(\d+)$/', $foreignAddr, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * 解析netstat监听行
     *
     * @return array{pid: string, ip: string, port: string}|null
     */
    private function parseNetstatListenLine(string $line): ?array
    {
        $parts = preg_split('/\s+/', trim($line));
        if (!is_array($parts) || count($parts) < 9) {
            return null;
        }

        $localAddr = $parts[3];
        if (1 !== preg_match('/(.+)\.(\d+)$/', $localAddr, $matches)) {
            return null;
        }

        return [
            'pid' => $parts[8],
            'ip' => '*' === $matches[1] ? '*' : $matches[1],
            'port' => $matches[2],
        ];
    }

    /**
     * 创建服务信息
     *
     * @param array{pid: string, ip: string, port: string} $serviceData
     */
    private function createServiceInfo(array $serviceData): ServiceInfoVO
    {
        $serviceName = $this->getProcessNameByPid($serviceData['pid']);
        $connectionInfo = $this->getConnectionInfoByPort($serviceData['port']);
        $resourceUsage = $this->getProcessResourceUsage($serviceData['pid']);

        return $this->buildServiceInfoVO($serviceData, $serviceName, $connectionInfo, $resourceUsage);
    }

    /**
     * 解析已建立连接行
     *
     * @return array{remoteIp: string, remotePort: string}|null
     */
    private function parseEstablishedConnectionLine(string $line): ?array
    {
        $parts = preg_split('/\s+/', trim($line));
        if (!is_array($parts) || count($parts) < 5) {
            return null;
        }

        $foreignAddr = $parts[4];
        if (1 !== preg_match('/(.+)\.(\d+)$/', $foreignAddr, $matches)) {
            return null;
        }

        return [
            'remoteIp' => $matches[1],
            'remotePort' => $matches[2],
        ];
    }

    /**
     * 创建连接信息
     *
     * @param array{remoteIp: string, remotePort: string} $connectionData
     */
    private function createConnectionInfo(array $connectionData): ConnectionInfoVO
    {
        return $this->buildConnectionInfoVO($connectionData);
    }

    /**
     * 收集进程连接数据
     *
     * @param array<int, string> $output
     * @return array<string, array{name: string, ips: array<int, string>, connections: int}>
     */
    private function collectProcessConnections(array $output): array
    {
        $processConnections = [];

        foreach ($output as $line) {
            $connectionData = $this->parseEstablishedProcessLine($line);
            if (null !== $connectionData) {
                $processConnections = $this->updateProcessConnection(
                    $processConnections,
                    $connectionData['pid'],
                    $connectionData['remoteIp']
                );
            }
        }

        return $processConnections;
    }

    /**
     * 创建进程信息
     *
     * @param array{name: string, ips: array<int, string>, connections: int} $info
     */
    private function createProcessInfo(string|int $pid, array $info): ProcessInfoVO
    {
        $pidString = (string) $pid;
        $resourceUsage = $this->getProcessResourceUsage($pidString);

        return $this->buildProcessInfoVO($pidString, $info, $resourceUsage);
    }

    /**
     * 解析已建立连接的进程行
     *
     * @return array{pid: string, remoteIp: string}|null
     */
    private function parseEstablishedProcessLine(string $line): ?array
    {
        $parts = preg_split('/\s+/', trim($line));
        if (!is_array($parts) || count($parts) < 9) {
            return null;
        }

        $pid = $parts[8];
        $foreignAddr = $parts[4];

        if (1 !== preg_match('/(.+)\.(\d+)$/', $foreignAddr, $matches)) {
            return null;
        }

        return ['pid' => $pid, 'remoteIp' => $matches[1]];
    }

    /**
     * 更新进程连接信息
     *
     * @param array<string, array{name: string, ips: array<int, string>, connections: int}> $processConnections
     * @return array<string, array{name: string, ips: array<int, string>, connections: int}>
     */
    private function updateProcessConnection(array $processConnections, string $pid, string $remoteIp): array
    {
        $processConnections = $this->ensureProcessExists($processConnections, $pid);
        $processConnections[$pid] = $this->addRemoteIpToProcess($processConnections[$pid], $remoteIp);
        ++$processConnections[$pid]['connections'];

        return $processConnections;
    }

    /**
     * 初始化进程连接信息
     *
     * @return array{name: string, ips: array<int, string>, connections: int}
     */
    private function initializeProcessConnection(string $pid): array
    {
        return [
            'name' => $this->getProcessNameByPid($pid),
            'ips' => [],
            'connections' => 0,
        ];
    }

    /**
     * 获取netstat监听输出
     *
     * @return array<int, string>
     */
    private function getNetstatListenOutput(): array
    {
        $command = 'netstat -anv -p tcp | grep LISTEN';

        return array_values($this->executeCommand($command));
    }

    /**
     * 获取netstat已建立连接输出
     *
     * @return array<int, string>
     */
    private function getNetstatEstablishedOutput(): array
    {
        $command = 'netstat -anv -p tcp | grep ESTABLISHED';

        return array_values($this->executeCommand($command));
    }

    /**
     * 从输出中解析服务数据
     *
     * @param array<int, string> $output
     * @return array<int, array{pid: string, ip: string, port: string}>
     */
    private function parseServicesFromOutput(array $output): array
    {
        $services = [];

        foreach ($output as $line) {
            $serviceData = $this->parseNetstatListenLine($line);
            if (null !== $serviceData) {
                $services[] = $serviceData;
            }
        }

        return $services;
    }

    /**
     * 构建服务信息集合
     *
     * @param array<int, array{pid: string, ip: string, port: string}> $services
     * @return Collection<int, ServiceInfoVO>
     */
    private function buildServicesCollection(array $services): Collection
    {
        $collection = new ArrayCollection();

        foreach ($services as $serviceData) {
            $serviceInfo = $this->createServiceInfo($serviceData);
            $collection->add($serviceInfo);
        }

        return $collection;
    }

    /**
     * 从输出中解析连接数据
     *
     * @param array<int, string> $output
     * @return array<int, array{remoteIp: string, remotePort: string}>
     */
    private function parseConnectionsFromOutput(array $output): array
    {
        $connections = [];

        foreach ($output as $line) {
            $connectionData = $this->parseEstablishedConnectionLine($line);
            if (null !== $connectionData) {
                $connections[] = $connectionData;
            }
        }

        return $connections;
    }

    /**
     * 过滤本地连接
     *
     * @param array<int, array{remoteIp: string, remotePort: string}> $connections
     * @return array<int, array{remoteIp: string, remotePort: string}>
     */
    private function filterLocalConnections(array $connections): array
    {
        return array_filter($connections, function (array $connectionData): bool {
            return '127.0.0.1' !== $connectionData['remoteIp'];
        });
    }

    /**
     * 构建连接信息集合
     *
     * @param array<int, array{remoteIp: string, remotePort: string}> $connections
     * @return Collection<int, ConnectionInfoVO>
     */
    private function buildConnectionsCollection(array $connections): Collection
    {
        $collection = new ArrayCollection();

        foreach ($connections as $connectionData) {
            $connectionInfo = $this->createConnectionInfo($connectionData);
            $collection->add($connectionInfo);
        }

        return $collection;
    }

    /**
     * 构建进程信息集合
     *
     * @param array<string, array{name: string, ips: array<int, string>, connections: int}> $processConnections
     * @return Collection<int, ProcessInfoVO>
     */
    private function buildProcessesCollection(array $processConnections): Collection
    {
        $collection = new ArrayCollection();

        foreach ($processConnections as $pid => $info) {
            $processInfo = $this->createProcessInfo($pid, $info);
            $collection->add($processInfo);
        }

        return $collection;
    }

    /**
     * 构建ServiceInfoVO实例
     *
     * @param array{pid: string, ip: string, port: string} $serviceData
     * @param array{ipCount: int, connCount: int} $connectionInfo
     */
    private function buildServiceInfoVO(array $serviceData, string $serviceName, array $connectionInfo, ProcessResourceUsageVO $resourceUsage): ServiceInfoVO
    {
        return new ServiceInfoVO(
            $serviceData['pid'],
            $serviceName,
            $serviceData['ip'],
            $serviceData['port'],
            $connectionInfo['ipCount'],
            $connectionInfo['connCount'],
            0, // 上传字节数，实际实现需要监控一段时间
            0, // 下载字节数，实际实现需要监控一段时间
            $resourceUsage->getCpu(),
            $resourceUsage->getMem()
        );
    }

    /**
     * 构建ConnectionInfoVO实例
     *
     * @param array{remoteIp: string, remotePort: string} $connectionData
     */
    private function buildConnectionInfoVO(array $connectionData): ConnectionInfoVO
    {
        return new ConnectionInfoVO(
            $connectionData['remoteIp'],
            $connectionData['remotePort'],
            1024, // 上传字节数，实际实现需要监控一段时间
            1024, // 下载字节数，实际实现需要监控一段时间
            '未知' // 地理位置需要通过GeoIP服务获取
        );
    }

    /**
     * 构建ProcessInfoVO实例
     *
     * @param array{name: string, ips: array<int, string>, connections: int} $info
     */
    private function buildProcessInfoVO(string $pid, array $info, ProcessResourceUsageVO $resourceUsage): ProcessInfoVO
    {
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
     * 提取接口名称
     */
    private function extractInterfaceName(string $line): ?string
    {
        if (1 === preg_match('/^([a-zA-Z0-9]+):/', $line, $matches) || 1 === preg_match('/^([a-zA-Z0-9]+)\s/', $line, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * 检查是否是回环接口
     */
    private function isLoopbackInterface(string $interfaceName): bool
    {
        return 'lo' === $interfaceName || 'lo0' === $interfaceName;
    }

    /**
     * 添加新接口
     *
     * @param array<string, array{tx: int, rx: int}> $interfaces
     * @return array{interfaces: array<string, array{tx: int, rx: int}>, currentInterface: string, found: bool}
     */
    private function addNewInterface(array $interfaces, string $interfaceName): array
    {
        if (!isset($interfaces[$interfaceName])) {
            $interfaces[$interfaceName] = ['tx' => 0, 'rx' => 0];
        }

        return ['interfaces' => $interfaces, 'currentInterface' => $interfaceName, 'found' => true];
    }

    /**
     * 提取接收字节数
     */
    private function extractRxBytes(string $line): ?int
    {
        if (1 === preg_match('/RX packets \d+\s+bytes (\d+)/', $line, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * 提取发送字节数
     */
    private function extractTxBytes(string $line): ?int
    {
        if (1 === preg_match('/TX packets \d+\s+bytes (\d+)/', $line, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * 更新连接计数
     *
     * @param array<int, string> $uniqueIps
     * @return array{uniqueIps: array<int, string>, connCount: int}
     */
    private function updateConnectionCounts(string $connectionData, array $uniqueIps, int $connCount): array
    {
        if (!in_array($connectionData, $uniqueIps, true)) {
            $uniqueIps[] = $connectionData;
        }
        ++$connCount;

        return ['uniqueIps' => $uniqueIps, 'connCount' => $connCount];
    }

    /**
     * 确保进程存在
     *
     * @param array<string, array{name: string, ips: array<int, string>, connections: int}> $processConnections
     * @return array<string, array{name: string, ips: array<int, string>, connections: int}>
     */
    private function ensureProcessExists(array $processConnections, string $pid): array
    {
        if (!isset($processConnections[$pid])) {
            $processConnections[$pid] = $this->initializeProcessConnection($pid);
        }

        return $processConnections;
    }

    /**
     * 添加远程IP到进程
     *
     * @param array{name: string, ips: array<int, string>, connections: int} $processData
     * @return array{name: string, ips: array<int, string>, connections: int}
     */
    private function addRemoteIpToProcess(array $processData, string $remoteIp): array
    {
        if (!in_array($remoteIp, $processData['ips'], true)) {
            $processData['ips'][] = $remoteIp;
        }

        return $processData;
    }

    /**
     * 检查PID是否有效
     */
    private function isValidPid(string $pid): bool
    {
        return is_numeric($pid) && (int) $pid <= 100000;
    }

    /**
     * 获取进程资源数据
     *
     * @return array{cpu: float, mem: float}
     */
    private function fetchProcessResourceData(string $pid): array
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
    private function fetchProcessName(string $pid): string
    {
        try {
            $command = "ps -p {$pid} -o comm= 2>/dev/null";
            $output = array_values($this->executeCommand($command));

            return count($output) > 0 ? trim($output[0]) : 'unknown';
        } catch (\Throwable $e) {
            return 'unknown';
        }
    }

    /**
     * 获取ifconfig输出
     *
     * @return array<int, string>
     */
    private function getIfconfigOutput(): array
    {
        return array_values($this->executeCommand('ifconfig'));
    }

    /**
     * 解析接口数据
     *
     * @param array<int, string> $output
     * @return array<string, array{tx: int, rx: int}>
     */
    private function parseInterfacesData(array $output): array
    {
        $interfaces = $this->parseIfconfigOutput($output);

        if ([] === $interfaces) {
            $interfaces = $this->parseIfconfigOutputFallback($output);
        }

        return $interfaces;
    }

    /**
     * 处理接口行
     *
     * @param array<string, array{tx: int, rx: int}> $interfaces
     * @return array{interfaces: array<string, array{tx: int, rx: int}>, currentInterface: string|null}
     */
    private function processInterfaceLine(string $line, array $interfaces, ?string $currentInterface): array
    {
        $result = $this->isNewInterfaceLine($line, $interfaces);
        if ($result['found']) {
            return ['interfaces' => $result['interfaces'], 'currentInterface' => $result['currentInterface']];
        }

        if (null !== $currentInterface && false !== strpos($line, 'bytes')) {
            $interfaces = $this->parseTrafficData($line, $currentInterface, $interfaces);
        }

        return ['interfaces' => $interfaces, 'currentInterface' => $currentInterface];
    }
}
