<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TmdTopBundle\Command\ConnectionsCommand;

/**
 * ConnectionsCommand 集成测试
 *
 * @internal
 */
#[CoversClass(ConnectionsCommand::class)]
#[RunTestsInSeparateProcesses]
#[Group('integration')]
final class ConnectionsCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getContainer()->get(ConnectionsCommand::class);
        $this->assertInstanceOf(ConnectionsCommand::class, $command);

        return new CommandTester($command);
    }

    public function testCommandConfiguration(): void
    {
        $command = self::getContainer()->get(ConnectionsCommand::class);
        $this->assertInstanceOf(ConnectionsCommand::class, $command);

        $this->assertSame('tmd-top:connections', $command->getName());
        $this->assertStringContainsString('显示网络连接信息', $command->getDescription());
    }

    public function testExecuteCommand(): void
    {
        $tester = $this->getCommandTester();

        // 命令需要 ConsoleOutputInterface，在非交互式环境下会返回 FAILURE
        $exitCode = $tester->execute([]);

        // 非交互式终端返回 FAILURE 是预期行为
        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('interactive terminal', $tester->getDisplay());
    }

    public function testOptionInterval(): void
    {
        $command = self::getContainer()->get(ConnectionsCommand::class);
        $this->assertInstanceOf(ConnectionsCommand::class, $command);

        // 验证 interval 选项存在
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('interval'));
        $this->assertSame('i', $definition->getOption('interval')->getShortcut());
    }

    public function testOptionCount(): void
    {
        $command = self::getContainer()->get(ConnectionsCommand::class);
        $this->assertInstanceOf(ConnectionsCommand::class, $command);

        // 验证 count 选项存在
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('count'));
        $this->assertSame('c', $definition->getOption('count')->getShortcut());
    }
}
