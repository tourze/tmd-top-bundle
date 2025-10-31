<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests\Adapter;

use Tourze\TmdTopBundle\Adapter\WindowsAdapter;

/**
 * 测试用的 WindowsAdapter，覆盖 executeCommand 方法以返回模拟数据
 *
 * @phpstan-ignore-next-line symplify.noExtends
 */
class TestableWindowsAdapter extends WindowsAdapter
{
    protected function executeCommand(string $command): array
    {
        return $this->getNetstatOutput($command)
            ?? $this->getTasklistOutput($command)
            ?? $this->getPowershellOutput($command)
            ?? $this->getMemoryOutput($command)
            ?? [];
    }

    /**
     * @return array<string>|null
     */
    private function getNetstatOutput(string $command): ?array
    {
        if (str_contains($command, 'netstat -ano | findstr LISTENING')) {
            return [
                '  TCP    0.0.0.0:80           0.0.0.0:0              LISTENING       1234',
                '  TCP    127.0.0.1:3306       0.0.0.0:0              LISTENING       5678',
            ];
        }

        if (str_contains($command, 'netstat -ano | findstr ESTABLISHED')) {
            return [
                '  TCP    192.168.1.100:22     203.0.113.1:54321      ESTABLISHED     1234',
                '  TCP    192.168.1.100:80     198.51.100.1:12345      ESTABLISHED     5678',
            ];
        }

        return null;
    }

    /**
     * @return array<string>|null
     */
    private function getTasklistOutput(string $command): ?array
    {
        if (!str_contains($command, 'tasklist /FI "PID eq')) {
            return null;
        }

        if (str_contains($command, '1234')) {
            return ['"nginx.exe","1234","Console","1","10,328 K"'];
        }

        if (str_contains($command, '5678')) {
            return ['"mysqld.exe","5678","Services","0","25,640 K"'];
        }

        return [];
    }

    /**
     * @return array<string>|null
     */
    private function getPowershellOutput(string $command): ?array
    {
        if (!str_contains($command, 'powershell.exe')) {
            return null;
        }

        if (str_contains($command, '1234')) {
            return ['"CPU","WorkingSetMB"', '"2.5","10.3"'];
        }

        if (str_contains($command, '5678')) {
            return ['"CPU","WorkingSetMB"', '"5.2","25.6"'];
        }

        return [];
    }

    /**
     * @return array<string>|null
     */
    private function getMemoryOutput(string $command): ?array
    {
        if (str_contains($command, 'Get-CimInstance')) {
            return ['TotalVisibleMemorySize', '8589934592'];
        }

        return null;
    }
}
