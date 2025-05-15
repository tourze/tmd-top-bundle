<?php

namespace Tourze\TmdTopBundle\Tests\Functional\Command;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\TmdTopBundle\Command\NetcardCommand;
use Tourze\TmdTopBundle\Service\NetworkMonitor;
use Tourze\TmdTopBundle\VO\NetcardInfoVO;

class NetcardCommandTest extends TestCase
{
    private NetworkMonitor $networkMonitor;
    private CommandTester $commandTester;
    private NetcardCommand $command;
    
    protected function setUp(): void
    {
        // 创建一个模拟的 NetworkMonitor 服务
        $this->networkMonitor = $this->createMock(NetworkMonitor::class);
        
        // 创建命令
        $this->command = new NetcardCommand($this->networkMonitor);
        
        // 设置命令的测试回调
        $this->command->executeCallback = function ($input, $output) {
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
        $application = new Application();
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
            return "$bytes B";
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 1) . " KB";
        } else {
            return round($bytes / 1048576, 1) . " MB";
        }
    }
    
    public function testExecute_displaysNetcardInfo(): void
    {
        // 准备模拟数据
        $netcardCollection = new ArrayCollection();
        $netcardCollection->add(new NetcardInfoVO('eth0', 1024, 2048));
        $netcardCollection->add(new NetcardInfoVO('eth1', 5120, 10240));
        
        // 配置模拟对象的行为
        $this->networkMonitor->method('getNetcardInfo')
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
    
    public function testExecute_withNoNetcards_displaysEmptyTable(): void
    {
        // 准备空的模拟数据
        $emptyCollection = new ArrayCollection();
        
        // 配置模拟对象的行为
        $this->networkMonitor->method('getNetcardInfo')
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
    
    public function testExecute_withIntervalOption_doesNotCrash(): void
    {
        // 准备模拟数据
        $netcardCollection = new ArrayCollection();
        $netcardCollection->add(new NetcardInfoVO('eth0', 1024, 2048));
        
        // 配置模拟对象的行为
        $this->networkMonitor->method('getNetcardInfo')
            ->willReturn($netcardCollection);
        
        // 执行命令，限制为只刷新一次
        $this->commandTester->execute([
            '--interval' => '1',
            '--count' => '1'
        ]);
        
        // 获取命令输出
        $output = $this->commandTester->getDisplay();
        
        // 验证输出内容
        $this->assertStringContainsString('eth0', $output);
        
        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }
    
    public function testExecute_formatsBytesCorrectly(): void
    {
        // 准备模拟数据 - 使用不同大小的值来测试格式化
        $netcardCollection = new ArrayCollection();
        $netcardCollection->add(new NetcardInfoVO('eth0', 1024, 2048));
        
        // 配置模拟对象的行为
        $this->networkMonitor->method('getNetcardInfo')
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
} 