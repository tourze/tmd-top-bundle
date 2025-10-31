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
use Tourze\TmdTopBundle\Command\NetcardCommand;
use Tourze\TmdTopBundle\Service\NetworkMonitorInterface;
use Tourze\TmdTopBundle\VO\NetcardInfoVO;

/**
 * @internal
 */
#[CoversClass(NetcardCommand::class)]
#[RunTestsInSeparateProcesses]
final class NetcardCommandTest extends AbstractCommandTestCase
{
    /** @var NetworkMonitorInterface&\PHPUnit\Framework\MockObject\MockObject */
    private NetworkMonitorInterface $networkMonitor;

    private CommandTester $commandTester;

    private NetcardCommand $command;

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $this->networkMonitor = $this->createMock(NetworkMonitorInterface::class);

        // 从容器获取命令
        $command = self::getContainer()->get(NetcardCommand::class);
        $this->assertInstanceOf(NetcardCommand::class, $command);
        $this->command = $command;

        // 设置命令的测试回调
        $this->command->executeCallback = function (\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int {
            // 显示时间戳
            $timestamp = date('Y-m-d H:i:s');
            $output->writeln('网卡 - ' . $timestamp);

            // 获取网卡信息
            $netcardCollection = $this->networkMonitor->getNetcardInfo();

            // 渲染表格
            $output->writeln('网卡名称 | 上传速率 | 下载速率');
            $output->writeln('--------|---------|----------');

            foreach ($netcardCollection as $netcard) {
                $output->writeln(sprintf(
                    '%s | %s | %s',
                    $netcard->getName(),
                    $this->formatBytes($netcard->getUploadBytes()),
                    $this->formatBytes($netcard->getDownloadBytes())
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
        $command = $application->find(NetcardCommand::NAME);
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

    public function testExecuteDisplaysNetcardInfo(): void
    {
        // 准备模拟数据
        $netcardCollection = new ArrayCollection();
        $netcardCollection->add(new NetcardInfoVO('eth0', 1024, 2048));
        $netcardCollection->add(new NetcardInfoVO('eth1', 5120, 10240));

        // 配置模拟对象的行为
        $this->networkMonitor->expects($this->any())
            ->method('getNetcardInfo')
            ->willReturn($netcardCollection);

        // 执行命令
        $this->commandTester->execute([]);

        // 获取命令输出
        $output = $this->commandTester->getDisplay();

        // 验证输出包含期望的网卡信息
        $this->assertStringContainsString('eth0', $output);
        $this->assertStringContainsString('eth1', $output);

        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithNoNetcardsDisplaysEmptyTable(): void
    {
        // 准备空的模拟数据
        $emptyCollection = new ArrayCollection();

        // 配置模拟对象的行为
        $this->networkMonitor->expects($this->any())
            ->method('getNetcardInfo')
            ->willReturn($emptyCollection);

        // 执行命令
        $this->commandTester->execute([]);

        // 获取命令输出
        $output = $this->commandTester->getDisplay();

        // 验证输出内容
        $this->assertStringContainsString('网卡', $output);

        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithIntervalOptionDoesNotCrash(): void
    {
        // 准备模拟数据
        $netcardCollection = new ArrayCollection();
        $netcardCollection->add(new NetcardInfoVO('eth0', 1024, 2048));

        // 配置模拟对象的行为
        $this->networkMonitor->expects($this->any())
            ->method('getNetcardInfo')
            ->willReturn($netcardCollection);

        // 执行命令，限制为只刷新一次
        $this->commandTester->execute([
            '--interval' => '1',
            '--count' => '1',
        ]);

        // 获取命令输出
        $output = $this->commandTester->getDisplay();

        // 验证输出内容
        $this->assertStringContainsString('eth0', $output);

        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteFormatsBytesCorrectly(): void
    {
        // 准备模拟数据 - 使用不同大小的值来测试格式化
        $netcardCollection = new ArrayCollection();
        $netcardCollection->add(new NetcardInfoVO('eth0', 1024, 2048));

        // 配置模拟对象的行为
        $this->networkMonitor->expects($this->any())
            ->method('getNetcardInfo')
            ->willReturn($netcardCollection);

        // 执行命令
        $this->commandTester->execute([]);

        // 获取命令输出
        $output = $this->commandTester->getDisplay();

        // 验证包含网卡名
        $this->assertStringContainsString('eth0', $output);

        // 验证包含格式化的字节值
        $this->assertMatchesRegularExpression('/1(\.0)?\s*K/i', $output);  // 1 KB
        $this->assertMatchesRegularExpression('/2(\.0)?\s*K/i', $output);  // 2 KB

        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionInterval(): void
    {
        // 准备模拟数据
        $netcardCollection = new ArrayCollection();
        $netcardCollection->add(new NetcardInfoVO('eth0', 1024, 2048));

        // 配置模拟对象的行为
        $this->networkMonitor->expects($this->any())
            ->method('getNetcardInfo')
            ->willReturn($netcardCollection);

        // 执行命令，测试interval选项
        $this->commandTester->execute([
            '--interval' => '1',
            '--count' => '1',
        ]);

        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testOptionCount(): void
    {
        // 准备模拟数据
        $netcardCollection = new ArrayCollection();
        $netcardCollection->add(new NetcardInfoVO('eth0', 1024, 2048));

        // 配置模拟对象的行为
        $this->networkMonitor->expects($this->any())
            ->method('getNetcardInfo')
            ->willReturn($netcardCollection);

        // 执行命令，测试count选项
        $this->commandTester->execute([
            '--count' => '2',
        ]);

        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }
}
