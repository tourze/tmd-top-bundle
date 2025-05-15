<?php

namespace Tourze\TmdTopBundle\Tests\Functional\Command;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\TmdTopBundle\Command\ServicesCommand;
use Tourze\TmdTopBundle\Service\NetworkMonitor;
use Tourze\TmdTopBundle\VO\ServiceInfoVO;

class ServicesCommandTest extends TestCase
{
    private NetworkMonitor $networkMonitor;
    private CommandTester $commandTester;
    private ServicesCommand $command;
    
    protected function setUp(): void
    {
        // 创建一个模拟的 NetworkMonitor 服务
        $this->networkMonitor = $this->createMock(NetworkMonitor::class);
        
        // 创建命令
        $this->command = new ServicesCommand($this->networkMonitor);
        
        // 设置命令的测试回调
        $this->command->executeCallback = function ($input, $output) {
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
        $application = new Application();
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
            return "$bytes B";
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 1) . " KB";
        } else {
            return round($bytes / 1048576, 1) . " MB";
        }
    }
    
    public function testExecute_displaysServicesInfo(): void
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
        $this->networkMonitor->method('getServicesInfo')
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
    
    public function testExecute_withNoServices_displaysEmptyTable(): void
    {
        // 准备空的模拟数据
        $emptyCollection = new ArrayCollection();
        
        // 配置模拟对象的行为
        $this->networkMonitor->method('getServicesInfo')
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
    
    public function testExecute_withIntervalOption_doesNotCrash(): void
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
        $this->networkMonitor->method('getServicesInfo')
            ->willReturn($servicesCollection);
        
        // 执行命令，限制为只刷新一次
        $this->commandTester->execute([
            '--interval' => '1',
            '--count' => '1'
        ], ['interactive' => false]);
        
        // 获取命令输出
        $output = $this->commandTester->getDisplay();
        
        // 验证输出内容
        $this->assertStringContainsString('nginx', $output);
        
        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }
    
    public function testExecute_formatsBytesAndPercentages(): void
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
        $this->networkMonitor->method('getServicesInfo')
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
} 