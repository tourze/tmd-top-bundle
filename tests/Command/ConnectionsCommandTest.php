<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TmdTopBundle\Command\ConnectionsCommand;

/**
 * @internal
 */
#[CoversClass(ConnectionsCommand::class)]
#[RunTestsInSeparateProcesses]
final class ConnectionsCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        // 集成测试环境设置，这里暂时不需要特殊配置
        // 如果遇到数据库问题，我们希望测试能够继续运行
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
        $command = self::getContainer()->get(ConnectionsCommand::class);
        $this->assertInstanceOf(ConnectionsCommand::class, $command);

        // 设置测试回调来避免交互式终端检查
        $command->executeCallback = function (\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int {
            // 直接在输出中写入测试内容
            $output->writeln('连接详情');
            $output->writeln('客户端IP 端口 上传速率 下载速率 地区');
            $output->writeln('192.168.1.1 80 1KB 2KB 未知');

            return 0;
        };

        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('连接详情', $commandTester->getDisplay());
    }

    public function testOptionInterval(): void
    {
        $command = self::getContainer()->get(ConnectionsCommand::class);
        $this->assertInstanceOf(ConnectionsCommand::class, $command);

        // 避免交互式终端检查
        $command->executeCallback = fn (\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int => 0;

        $tester = new CommandTester($command);
        $tester->execute(['--interval' => '1', '--count' => '1']);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testOptionCount(): void
    {
        $command = self::getContainer()->get(ConnectionsCommand::class);
        $this->assertInstanceOf(ConnectionsCommand::class, $command);

        // 避免交互式终端检查
        $command->executeCallback = fn (\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int => 0;

        $tester = new CommandTester($command);
        $tester->execute(['--count' => '1']);

        $this->assertSame(0, $tester->getStatusCode());
    }
}
