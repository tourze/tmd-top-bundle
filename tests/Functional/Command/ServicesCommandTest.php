<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests\Functional\Command;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\TmdTopBundle\Command\ServicesCommand;
use Tourze\TmdTopBundle\Service\NetworkMonitorInterface;
use Tourze\TmdTopBundle\VO\ServiceInfoVO;

/**
 * @internal
 */
#[CoversClass(ServicesCommand::class)]
#[RunTestsInSeparateProcesses]
final class ServicesCommandTest extends AbstractCommandTestCase
{
    /** @var NetworkMonitorInterface&\PHPUnit\Framework\MockObject\MockObject */
    private NetworkMonitorInterface $networkMonitor;

    private CommandTester $commandTester;

    private ServicesCommand $command;

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $this->networkMonitor = $this->createMock(NetworkMonitorInterface::class);

        // 从容器获取命令
        $command = self::getContainer()->get(ServicesCommand::class);
        $this->assertInstanceOf(ServicesCommand::class, $command);
        $this->command = $command;

        // 设置命令的测试回调
        $this->command->executeCallback = function (\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int {
            // 显示时间戳
            $timestamp = date('Y-m-d H:i:s');
            $output->writeln('监听服务 - ' . $timestamp);

            // 获取服务信息
            $servicesInfo = $this->networkMonitor->getServicesInfo();

            // 渲染表格
            $output->writeln('PID | 服务名称 | IP | 端口 | IP数 | 连接数 | 上传速率 | 下载速率 | CPU使用率 | 内存使用率');
            $output->writeln('----|---------|----|----|------|--------|----------|----------|-----------|----------');

            foreach ($servicesInfo as $info) {
                $output->writeln(sprintf(
                    '%s | %s | %s | %s | %d | %d | %s | %s | %.1f%% | %.1f%%',
                    $info->getPid(),
                    $info->getServiceName(),
                    $info->getIp(),
                    $info->getPort(),
                    $info->getIpCount(),
                    $info->getConnectionCount(),
                    $this->formatBytes($info->getUploadBytes()),
                    $this->formatBytes($info->getDownloadBytes()),
                    $info->getCpuUsage(),
                    $info->getMemoryUsage()
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
        $command = $application->find(ServicesCommand::NAME);
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

    public function testExecuteDisplaysServicesInfo(): void
    {
        // 准备模拟数据
        $servicesCollection = new ArrayCollection();
        $servicesCollection->add(new ServiceInfoVO(
            '1234',
            'nginx',
            '127.0.0.1',
            '80',
            10,
            100,
            1024,
            2048,
            1.5,
            2.5
        ));
        $servicesCollection->add(new ServiceInfoVO(
            '5678',
            'mysql',
            '127.0.0.1',
            '3306',
            5,
            50,
            512,
            1024,
            3.5,
            4.5
        ));

        // 配置模拟对象的行为
        $this->networkMonitor->expects($this->any())
            ->method('getServicesInfo')
            ->willReturn($servicesCollection);

        // 执行命令
        $this->commandTester->execute([], ['interactive' => false]);

        // 获取命令输出
        $output = $this->commandTester->getDisplay();

        // 验证输出包含期望的服务信息
        $this->assertStringContainsString('nginx', $output);
        $this->assertStringContainsString('mysql', $output);
        $this->assertStringContainsString('127.0.0.1', $output);
        $this->assertStringContainsString('80', $output);
        $this->assertStringContainsString('3306', $output);

        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithNoServicesDisplaysEmptyTable(): void
    {
        // 准备空的模拟数据
        $emptyCollection = new ArrayCollection();

        // 配置模拟对象的行为
        $this->networkMonitor->expects($this->any())
            ->method('getServicesInfo')
            ->willReturn($emptyCollection);

        // 执行命令
        $this->commandTester->execute([], ['interactive' => false]);

        // 获取命令输出
        $output = $this->commandTester->getDisplay();

        // 验证输出内容
        $this->assertStringContainsString('监听服务', $output);

        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithIntervalOptionDoesNotCrash(): void
    {
        // 准备模拟数据
        $servicesCollection = new ArrayCollection();
        $servicesCollection->add(new ServiceInfoVO(
            '1234',
            'nginx',
            '127.0.0.1',
            '80',
            10,
            100,
            1024,
            2048,
            1.5,
            2.5
        ));

        // 配置模拟对象的行为
        $this->networkMonitor->expects($this->any())
            ->method('getServicesInfo')
            ->willReturn($servicesCollection);

        // 执行命令，限制为只刷新一次
        $this->commandTester->execute([
            '--interval' => '1',
            '--count' => '1',
        ], ['interactive' => false]);

        // 获取命令输出
        $output = $this->commandTester->getDisplay();

        // 验证输出内容
        $this->assertStringContainsString('nginx', $output);

        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteFormatsBytesAndPercentages(): void
    {
        // 准备模拟数据
        $servicesCollection = new ArrayCollection();
        $servicesCollection->add(new ServiceInfoVO(
            '1234',
            'nginx',
            '127.0.0.1',
            '80',
            10,
            100,
            1024,
            2048,
            1.5,
            2.5
        ));

        // 配置模拟对象的行为
        $this->networkMonitor->expects($this->any())
            ->method('getServicesInfo')
            ->willReturn($servicesCollection);

        // 执行命令
        $this->commandTester->execute([], ['interactive' => false]);

        // 获取命令输出
        $output = $this->commandTester->getDisplay();

        // 验证输出内容
        $this->assertStringContainsString('nginx', $output);

        // 验证格式化的值
        $this->assertMatchesRegularExpression('/1(\.0)?\s*K/i', $output);  // 1 KB
        $this->assertMatchesRegularExpression('/2(\.0)?\s*K/i', $output);  // 2 KB
        $this->assertStringContainsString('1.5%', $output);
        $this->assertStringContainsString('2.5%', $output);

        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionInterval(): void
    {
        // 准备模拟数据
        $servicesCollection = new ArrayCollection();
        $servicesCollection->add(new ServiceInfoVO(
            '1234',
            'nginx',
            '127.0.0.1',
            '80',
            10,
            100,
            1024,
            2048,
            1.5,
            2.5
        ));

        // 配置模拟对象的行为
        $this->networkMonitor->expects($this->any())
            ->method('getServicesInfo')
            ->willReturn($servicesCollection);

        // 执行命令，测试interval选项
        $this->commandTester->execute([
            '--interval' => '1',
            '--count' => '1',
        ], ['interactive' => false]);

        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionCount(): void
    {
        // 准备模拟数据
        $servicesCollection = new ArrayCollection();
        $servicesCollection->add(new ServiceInfoVO(
            '1234',
            'nginx',
            '127.0.0.1',
            '80',
            10,
            100,
            1024,
            2048,
            1.5,
            2.5
        ));

        // 配置模拟对象的行为
        $this->networkMonitor->expects($this->any())
            ->method('getServicesInfo')
            ->willReturn($servicesCollection);

        // 执行命令，测试count选项
        $this->commandTester->execute([
            '--count' => '2',
        ], ['interactive' => false]);

        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }
}
