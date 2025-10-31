<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests\Adapter;

use Tourze\TmdTopBundle\Adapter\LinuxAdapter;

/**
 * 测试用的 LinuxAdapter，覆盖 executeCommand 方法以返回模拟数据
 *
 * @phpstan-ignore-next-line symplify.noExtends
 */
class TestableLinuxAdapter extends LinuxAdapter
{
    protected function executeCommand(string $command): array
    {
        $result = $this->getNetstatOutput($command);
        if (null !== $result) {
            return $result;
        }

        $result = $this->getSsOutput($command);
        if (null !== $result) {
            return $result;
        }

        $result = $this->getPsOutput($command);
        if (null !== $result) {
            return $result;
        }

        $result = $this->getMemoryOutput($command);
        if (null !== $result) {
            return $result;
        }

        $result = $this->getConnectionStatsOutput($command);
        if (null !== $result) {
            return $result;
        }

        return [];
    }

    /**
     * @return array<string>|null
     */
    private function getNetstatOutput(string $command): ?array
    {
        if (str_contains($command, 'netstat -tlnp') || str_contains($command, 'netstat -tulpn | grep LISTEN')) {
            return [
                'tcp        0      0 0.0.0.0:80              0.0.0.0:*               LISTEN      1234/nginx',
                'tcp        0      0 127.0.0.1:3306          0.0.0.0:*               LISTEN      5678/mysqld',
            ];
        }

        if (str_contains($command, 'netstat -tnp')) {
            return [
                'tcp        0      0 192.168.1.100:22        203.0.113.1:54321       ESTABLISHED 1234/nginx',
                'tcp        0      0 192.168.1.100:80        198.51.100.1:12345      ESTABLISHED 5678/mysqld',
            ];
        }

        if (str_contains($command, 'netstat -tn | grep ESTABLISHED')) {
            return [
                'tcp        0      0 192.168.1.100:22        203.0.113.1:54321       ESTABLISHED',
                'tcp        0      0 192.168.1.100:80        198.51.100.1:12345      ESTABLISHED',
            ];
        }

        return null;
    }

    /**
     * @return array<string>|null
     */
    private function getSsOutput(string $command): ?array
    {
        if (str_contains($command, 'ss -tunp | grep ESTAB')) {
            return [
                'tcp   ESTAB 0      0      192.168.1.100:22      203.0.113.1:54321    users:(("sshd",pid=1234,fd=3),("sshd",pid=1234,fd=4))',
                'tcp   ESTAB 0      0      192.168.1.100:80      198.51.100.1:12345   users:(("nginx",pid=5678,fd=5),("nginx",pid=5678,fd=6))',
            ];
        }

        return null;
    }

    /**
     * @return array<string>|null
     */
    private function getPsOutput(string $command): ?array
    {
        if (!str_contains($command, 'ps -p')) {
            return null;
        }

        if (str_contains($command, '-o comm=')) {
            return $this->getProcessNameOutput($command);
        }

        return $this->getProcessStatsOutput($command);
    }

    /**
     * @return array<string>
     */
    private function getProcessNameOutput(string $command): array
    {
        if (str_contains($command, '1234')) {
            return ['nginx'];
        }
        if (str_contains($command, '5678')) {
            return ['mysqld'];
        }

        return [];
    }

    /**
     * @return array<string>
     */
    private function getProcessStatsOutput(string $command): array
    {
        if (str_contains($command, '1234')) {
            return ['%CPU %MEM', '2.5 10.3'];
        }
        if (str_contains($command, '5678')) {
            return ['%CPU %MEM', '5.2 25.6'];
        }

        return [];
    }

    /**
     * @return array<string>|null
     */
    private function getMemoryOutput(string $command): ?array
    {
        if (str_contains($command, 'free -b')) {
            return ['Mem: 8589934592 2147483648 6442450944'];
        }

        return null;
    }

    /**
     * @return array<string>|null
     */
    private function getConnectionStatsOutput(string $command): ?array
    {
        if (str_contains($command, 'netstat -an | grep :') && str_contains($command, 'uniq -c')) {
            return [
                '      1 203.0.113.1',
                '      2 198.51.100.1',
            ];
        }

        return null;
    }
}
