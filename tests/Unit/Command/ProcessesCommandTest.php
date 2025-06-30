<?php

namespace Tourze\TmdTopBundle\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tourze\TmdTopBundle\Command\ProcessesCommand;
use Tourze\TmdTopBundle\Service\NetworkMonitor;

class ProcessesCommandTest extends TestCase
{
    private ProcessesCommand $command;
    private NetworkMonitor $networkMonitor;

    protected function setUp(): void
    {
        $this->networkMonitor = $this->createMock(NetworkMonitor::class);
        $this->command = new ProcessesCommand($this->networkMonitor);
    }

    public function testCommandName(): void
    {
        $this->assertSame('tmd-top:processes', ProcessesCommand::NAME);
        $this->assertSame('tmd-top:processes', $this->command->getName());
    }

    public function testCommandDescription(): void
    {
        $this->assertSame('显示当前运行的程序', $this->command->getDescription());
    }

    public function testExecuteWithCallback(): void
    {
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $callbackInvoked = false;

        $this->command->executeCallback = function () use (&$callbackInvoked) {
            $callbackInvoked = true;
            return Command::SUCCESS;
        };

        // 使用反射直接调用 execute 方法
        $reflection = new \ReflectionClass($this->command);
        $executeMethod = $reflection->getMethod('execute');
        $executeMethod->setAccessible(true);

        $exitCode = $executeMethod->invoke($this->command, $input, $output);

        $this->assertTrue($callbackInvoked);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testExecuteCallbackReturnsFailure(): void
    {
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $this->command->executeCallback = function () {
            return Command::FAILURE;
        };

        // 使用反射直接调用 execute 方法
        $reflection = new \ReflectionClass($this->command);
        $executeMethod = $reflection->getMethod('execute');
        $executeMethod->setAccessible(true);

        $exitCode = $executeMethod->invoke($this->command, $input, $output);

        $this->assertSame(Command::FAILURE, $exitCode);
    }

    public function testHasIntervalOption(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('interval'));

        $option = $definition->getOption('interval');
        $this->assertSame('i', $option->getShortcut());
        $this->assertNull($option->getDefault());
    }

    public function testHasCountOption(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('count'));

        $option = $definition->getOption('count');
        $this->assertSame('c', $option->getShortcut());
        $this->assertNull($option->getDefault());
    }
}
