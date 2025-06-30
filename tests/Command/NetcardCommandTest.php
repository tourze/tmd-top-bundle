<?php

namespace Tourze\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tourze\TmdTopBundle\Command\NetcardCommand;

class NetcardCommandTest extends TestCase
{
    private NetcardCommand $command;

    protected function setUp(): void
    {
        $networkMonitor = $this->createMock(\Tourze\TmdTopBundle\Service\NetworkMonitor::class);
        $this->command = new NetcardCommand($networkMonitor);
    }

    public function testCommandConfiguration(): void
    {
        $this->assertSame('tmd-top:netcard', $this->command->getName());
        $this->assertStringContainsString('显示网卡信息和流量状态', $this->command->getDescription());
    }

    public function testExecuteCommand(): void
    {
        $this->command->executeCallback = function() {
            return 0;
        };
        
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('execute');
        $method->setAccessible(true);

        $exitCode = $method->invoke($this->command, $input, $output);

        $this->assertSame(0, $exitCode);
    }

    public function testExecuteCallback(): void
    {
        $called = false;
        $this->command->executeCallback = function() use (&$called) {
            $called = true;
            return 0;
        };

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('execute');
        $method->setAccessible(true);

        $exitCode = $method->invoke($this->command, $input, $output);

        $this->assertTrue($called);
        $this->assertSame(0, $exitCode);
    }
} 