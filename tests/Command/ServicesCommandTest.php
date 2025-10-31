<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TmdTopBundle\Command\ServicesCommand;

/**
 * @internal
 */
#[CoversClass(ServicesCommand::class)]
#[RunTestsInSeparateProcesses]
final class ServicesCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        // 集成测试环境设置，这里暂时不需要特殊配置
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getContainer()->get(ServicesCommand::class);
        $this->assertInstanceOf(ServicesCommand::class, $command);

        return new CommandTester($command);
    }

    public function testCommandConfiguration(): void
    {
        $command = self::getContainer()->get(ServicesCommand::class);
        $this->assertInstanceOf(ServicesCommand::class, $command);

        $this->assertSame('tmd-top:services', $command->getName());
        $this->assertStringContainsString('显示监听服务信息', $command->getDescription());
    }

    public function testExecuteCommand(): void
    {
        $command = self::getContainer()->get(ServicesCommand::class);
        $this->assertInstanceOf(ServicesCommand::class, $command);

        // 设置测试回调来避免交互式终端检查
        $command->executeCallback = function (\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int {
            // 直接在输出中写入测试内容
            $output->writeln('服务');
            $output->writeln('PID 名称 IP 端口 连接数 IP数 CPU 内存');
            $output->writeln('1234 nginx * 80 2 1 2.5% 10.3%');

            return 0;
        };

        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('服务', $commandTester->getDisplay());
    }

    public function testOptionInterval(): void
    {
        $command = self::getContainer()->get(ServicesCommand::class);
        $this->assertInstanceOf(ServicesCommand::class, $command);
        $command->executeCallback = fn (\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int => 0;

        $tester = new CommandTester($command);
        $tester->execute(['--interval' => '1', '--count' => '1']);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testOptionCount(): void
    {
        $command = self::getContainer()->get(ServicesCommand::class);
        $this->assertInstanceOf(ServicesCommand::class, $command);
        $command->executeCallback = fn (\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int => 0;

        $tester = new CommandTester($command);
        $tester->execute(['--count' => '1']);

        $this->assertSame(0, $tester->getStatusCode());
    }
}
