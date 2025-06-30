<?php

namespace Tourze\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tourze\TmdTopBundle\Command\ProcessesCommand;

class ProcessesCommandTest extends TestCase
{
    private ProcessesCommand $command;

    protected function setUp(): void
    {
        $networkMonitor = $this->createMock(\Tourze\TmdTopBundle\Service\NetworkMonitor::class);
        $this->command = new ProcessesCommand($networkMonitor);
    }

    public function testCommandConfiguration(): void
    {
        $this->assertSame('tmd-top:processes', $this->command->getName());
        $this->assertStringContainsString('显示当前运行的程序', $this->command->getDescription());
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