<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests\Adapter;

use Tourze\TmdTopBundle\Adapter\MacOSAdapter;

/**
 * 测试用的 MacOSAdapter，覆盖 executeCommand 方法以返回模拟数据
 *
 * @phpstan-ignore-next-line symplify.noExtends
 */
class TestableMacOSAdapter extends MacOSAdapter
{
    protected function executeCommand(string $command): array
    {
        return $this->getNetstatOutput($command)
            ?? $this->getLsofOutput($command)
            ?? $this->getPsOutput($command)
            ?? [];
    }

    /**
     * @return array<string>|null
     */
    private function getNetstatOutput(string $command): ?array
    {
        if (str_contains($command, 'netstat -anv -p tcp | grep LISTEN')) {
            return [
                'tcp4       0      0  *.80                   *.*                    LISTEN      1234 0 0x0000 0x0000 0x0000 0x0000 0x0000 0x0000 0x0000',
                'tcp4       0      0  127.0.0.1.3306         *.*                    LISTEN      5678 0 0x0000 0x0000 0x0000 0x0000 0x0000 0x0000 0x0000',
            ];
        }

        if (str_contains($command, 'netstat -anv -p tcp | grep ESTABLISHED')) {
            return [
                'tcp4       0      0  192.168.1.100.22       203.0.113.1.54321      ESTABLISHED      1234 0 0x0000 0x0000 0x0000 0x0000 0x0000 0x0000 0x0000',
                'tcp4       0      0  192.168.1.100.80       198.51.100.1.12345      ESTABLISHED      5678 0 0x0000 0x0000 0x0000 0x0000 0x0000 0x0000 0x0000',
            ];
        }

        return null;
    }

    /**
     * @return array<string>|null
     */
    private function getLsofOutput(string $command): ?array
    {
        if (str_contains($command, 'lsof -i')) {
            return [
                'COMMAND   PID   USER   FD   TYPE             DEVICE SIZE/OFF NODE NAME',
                'sshd    1234   root    3u  IPv4 0x1234567890abcdef      0t0  TCP 192.168.1.100:22->203.0.113.1:54321 (ESTABLISHED)',
                'nginx   5678   root    5u  IPv4 0x0987654321fedcba      0t0  TCP 192.168.1.100:80->198.51.100.1:12345 (ESTABLISHED)',
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

        if (str_contains($command, '1234')) {
            return $this->getProcessOutput($command, ['nginx'], ['%CPU %MEM', '2.5 10.3']);
        }

        if (str_contains($command, '5678')) {
            return $this->getProcessOutput($command, ['mysqld'], ['%CPU %MEM', '5.2 15.7']);
        }

        return [];
    }

    /**
     * @param array<string> $name
     * @param array<string> $stats
     * @return array<string>
     */
    private function getProcessOutput(string $command, array $name, array $stats): array
    {
        if (str_contains($command, '-o comm=')) {
            return $name;
        }

        return $stats;
    }
}
