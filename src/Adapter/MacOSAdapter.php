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
            $interfaceName = $this->extractInterfaceName($line);

            if (null !== $interfaceName) {
                if (!$this->isLoopbackInterface($interfaceName)) {
                    $interfaces[$interfaceName] = ['tx' => 0, 'rx' => 0];
                    $currentInterface = $interfaceName;
                } else {
                    $currentInterface = null;
                }
                continue;
            }

            if (null !== $currentInterface && str_contains($line, 'bytes')) {
                $this->updateInterfaceTraffic($interfaces, $currentInterface, $line);
            }
        }

        return $interfaces;
    }

    /**
     * 更新接口流量数据
     *
     * @param array<string, array{tx: int, rx: int}> $interfaces
     */
    private function updateInterfaceTraffic(array &$interfaces, string $interfaceName, string $line): void
    {
        $rxBytes = $this->extractBytes($line, 'RX');
        if (null !== $rxBytes) {
            $interfaces[$interfaceName]['rx'] = $rxBytes;
        }

        $txBytes = $this->extractBytes($line, 'TX');
        if (null !== $txBytes) {
            $interfaces[$interfaceName]['tx'] = $txBytes;
        }
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
        $output = $this->getNetstatOutput('LISTEN');
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
        $output = $this->getNetstatOutput('ESTABLISHED');
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
        $output = $this->getNetstatOutput('ESTABLISHED');
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
            if (null === $connectionData) {
                continue;
            }
            if (!in_array($connectionData, $uniqueIps, true)) {
                $uniqueIps[] = $connectionData;
            }
            ++$connCount;
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
            if (null === $connectionData) {
                continue;
            }

            $pid = $connectionData['pid'];
            $remoteIp = $connectionData['remoteIp'];

            if (!isset($processConnections[$pid])) {
                $processConnections[$pid] = [
                    'name' => $this->getProcessNameByPid($pid),
                    'ips' => [],
                    'connections' => 0,
                ];
            }

            if (!in_array($remoteIp, $processConnections[$pid]['ips'], true)) {
                $processConnections[$pid]['ips'][] = $remoteIp;
            }
            ++$processConnections[$pid]['connections'];
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
     * 获取netstat输出
     *
     * @return array<int, string>
     */
    private function getNetstatOutput(string $filter): array
    {
        $command = "netstat -anv -p tcp | grep {$filter}";

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
            $collection->add($this->buildConnectionInfoVO($connectionData));
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
     * 提取字节数
     */
    private function extractBytes(string $line, string $direction): ?int
    {
        $pattern = "/{$direction} packets \\d+\\s+bytes (\\d+)/";
        if (1 === preg_match($pattern, $line, $matches)) {
            return (int) $matches[1];
        }

        return null;
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
}
