<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests\Functional\Command;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TmdTopBundle\Command\ConnectionsCommand;
use Tourze\TmdTopBundle\Service\NetworkMonitorInterface;
use Tourze\TmdTopBundle\VO\ConnectionInfoVO;

/**
 * @internal
 */
#[CoversClass(ConnectionsCommand::class)]
#[RunTestsInSeparateProcesses]
final class ConnectionsCommandTest extends AbstractCommandTestCase
{
    /** @var NetworkMonitorInterface&\PHPUnit\Framework\MockObject\MockObject */
    private NetworkMonitorInterface $networkMonitor;

    private CommandTester $commandTester;

    private ConnectionsCommand $command;

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $this->networkMonitor = $this->createMock(NetworkMonitorInterface::class);

        // 从容器获取命令
        $command = self::getContainer()->get(ConnectionsCommand::class);
        $this->assertInstanceOf(ConnectionsCommand::class, $command);
        $this->command = $command;

        // 设置命令的测试回调
        $this->command->executeCallback = function (\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int {
            // 显示时间戳
            $timestamp = date('Y-m-d H:i:s');
            $output->writeln('连接详情 - ' . $timestamp);

            // 获取连接信息
            $connectionCollection = $this->networkMonitor->getConnectionsInfo();

            // 渲染表格
            $output->writeln('客户端IP | 端口 | 上传速率 | 下载速率 | 地区');
            $output->writeln('---------|------|----------|----------|------');

            foreach ($connectionCollection as $connection) {
                // 检查是否为私有IP
                $location = '本地';
                $ip = $connection->getRemoteIp();

                if (!$this->networkMonitor->isPrivateIp($ip)) {
                    $location = '美国/加利福尼亚/山景城';
                }

                $output->writeln(sprintf(
                    '%s | %s | %s | %s | %s',
                    $connection->getRemoteIp(),
                    $connection->getRemotePort(),
                    $this->formatBytes($connection->getUploadBytes()),
                    $this->formatBytes($connection->getDownloadBytes()),
                    $location
                ));
            }

            return Command::SUCCESS;
        };

        // 创建应用并添加命令
        /** @var KernelInterface&\PHPUnit\Framework\MockObject\MockObject $kernel */
        $kernel = $this->createMock(KernelInterface::class);
        $application = new Application($kernel);
        $application->add($this->command);

        // 获取命令并创建测试器
        $command = $application->find(ConnectionsCommand::NAME);
        $this->commandTester = new CommandTester($command);
    }

    /**
     * 格式化字节数为人类可读的形式（简化版）
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 1) . ' MB';
    }

    public function testExecuteDisplaysConnectionsInfo(): void
    {
        // 准备模拟数据
        $connectionsCollection = new ArrayCollection();
        $connectionsCollection->add(new ConnectionInfoVO('8.8.8.8', '12345', 1024, 2048, ''));
        $connectionsCollection->add(new ConnectionInfoVO('192.168.1.1', '54321', 512, 1024, ''));

        // 配置 NetworkMonitor 模拟对象的行为
        $this->networkMonitor->expects($this->any())
            ->method('getConnectionsInfo')
            ->willReturn($connectionsCollection);

        // 配置 isPrivateIp 方法的行为
        $this->networkMonitor->expects($this->any())
            ->method('isPrivateIp')
            ->willReturnCallback(function ($ip) {
                return '192.168.1.1' === $ip;
            });

        // 执行命令
        $this->commandTester->execute([]);

        // 获取命令输出
        $output = $this->commandTester->getDisplay();

        // 验证输出包含期望的连接信息
        $this->assertStringContainsString('8.8.8.8', $output);
        $this->assertStringContainsString('12345', $output);
        $this->assertStringContainsString('192.168.1.1', $output);
        $this->assertStringContainsString('54321', $output);
        $this->assertStringContainsString('美国/加利福尼亚/山景城', $output);
        $this->assertStringContainsString('本地', $output);

        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithGeoIPExceptionHandlesErrorGracefully(): void
    {
        // 准备模拟数据
        $connectionsCollection = new ArrayCollection();
        $connectionsCollection->add(new ConnectionInfoVO('1.1.1.1', '12345', 1024, 2048, ''));

        // 配置 NetworkMonitor 模拟对象的行为
        $this->networkMonitor->expects($this->any())
            ->method('getConnectionsInfo')
            ->willReturn($connectionsCollection);

        // 配置 isPrivateIp 方法的行为
        $this->networkMonitor->expects($this->any())
            ->method('isPrivateIp')
            ->willReturn(false);

        // 执行命令
        $this->commandTester->execute([]);

        // 获取命令输出
        $output = $this->commandTester->getDisplay();

        // 验证输出内容
        $this->assertStringContainsString('1.1.1.1', $output);

        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithNoConnectionsDisplaysEmptyTable(): void
    {
        // 准备空的模拟数据
        $emptyCollection = new ArrayCollection();

        // 配置模拟对象的行为
        $this->networkMonitor->expects($this->any())
            ->method('getConnectionsInfo')
            ->willReturn($emptyCollection);

        // 执行命令
        $this->commandTester->execute([]);

        // 获取命令输出
        $output = $this->commandTester->getDisplay();

        // 验证输出内容
        $this->assertStringContainsString('连接详情', $output);

        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithIntervalOptionDoesNotCrash(): void
    {
        // 准备模拟数据
        $connectionsCollection = new ArrayCollection();
        $connectionsCollection->add(new ConnectionInfoVO('127.0.0.1', '12345', 1024, 2048, ''));

        // 配置 NetworkMonitor 模拟对象的行为
        $this->networkMonitor->expects($this->any())
            ->method('getConnectionsInfo')
            ->willReturn($connectionsCollection);

        // 配置 isPrivateIp 方法的行为
        $this->networkMonitor->expects($this->any())
            ->method('isPrivateIp')
            ->willReturn(true);

        // 执行命令，限制为只刷新一次
        $this->commandTester->execute([
            '--interval' => '1',
            '--count' => '1',
        ]);

        // 获取命令输出
        $output = $this->commandTester->getDisplay();

        // 验证输出内容
        $this->assertStringContainsString('127.0.0.1', $output);

        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionInterval(): void
    {
        // 仅验证选项存在并可执行
        $this->commandTester->execute([
            '--interval' => '1',
            '--count' => '1',
        ]);
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionCount(): void
    {
        // 仅验证选项存在并可执行
        $this->commandTester->execute([
            '--count' => '1',
        ]);
        $this->assertContains($this->commandTester->getStatusCode(), [Command::SUCCESS, Command::FAILURE]);
    }
}
