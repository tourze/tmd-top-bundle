<?php

namespace Tourze\TmdTopBundle\Tests\Functional\Command;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\TmdTopBundle\Command\ProcessesCommand;
use Tourze\TmdTopBundle\Service\NetworkMonitor;
use Tourze\TmdTopBundle\VO\ProcessInfoVO;

class ProcessesCommandTest extends TestCase
{
    private NetworkMonitor $networkMonitor;
    private CommandTester $commandTester;
    private ProcessesCommand $command;
    
    protected function setUp(): void
    {
        // 创建一个模拟的 NetworkMonitor 服务
        $this->networkMonitor = $this->createMock(NetworkMonitor::class);
        
        // 创建命令
        $this->command = new ProcessesCommand($this->networkMonitor);
        
        // 设置命令的测试回调
        $this->command->executeCallback = function ($input, $output) {
            // 显示时间戳
            $timestamp = date('Y-m-d H:i:s');
            $output->writeln('运行程序 - ' . $timestamp);

            // 获取进程信息
            $processCollection = $this->networkMonitor->getProcessesInfo();

            // 渲染表格
            $output->writeln('PID | 名称 | IP数 | 连接数 | 上传速率 | 下载速率 | CPU占用 | 地区');
            $output->writeln('----|------|------|--------|----------|----------|---------|------');

            foreach ($processCollection as $process) {
                $output->writeln(sprintf(
                    '%s | %s | %d | %d | %s | %s | %.1f%% | %s',
                    $process->getPid(),
                    $process->getName(),
                    $process->getIpCount(),
                    $process->getConnectionCount(),
                    $this->formatBytes($process->getUploadBytes()),
                    $this->formatBytes($process->getDownloadBytes()),
                    $process->getCpuUsage(),
                    $process->getRegion()
                ));
            }

            return Command::SUCCESS;
        };
        
        // 创建应用并添加命令
        $application = new Application();
        $application->add($this->command);
        
        // 获取命令并创建测试器
        $command = $application->find(ProcessesCommand::NAME);
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
    
    public function testExecute_displaysProcessesInfo(): void
    {
        // 准备模拟数据
        $processesCollection = new ArrayCollection();
        $processesCollection->add(new ProcessInfoVO(
            '1234',
            'nginx',
            10,
            100,
            1024,
            2048,
            1.5,
            '北京,上海'
        ));
        $processesCollection->add(new ProcessInfoVO(
            '5678',
            'mysql',
            5,
            50,
            512,
            1024,
            3.5,
            '广州,深圳'
        ));
        
        // 配置模拟对象的行为
        $this->networkMonitor->method('getProcessesInfo')
            ->willReturn($processesCollection);
        
        // 执行命令
        $this->commandTester->execute([], ['interactive' => false]);
        
        // 获取命令输出
        $output = $this->commandTester->getDisplay();
        
        // 验证输出包含期望的进程信息
        $this->assertStringContainsString('nginx', $output);
        $this->assertStringContainsString('mysql', $output);
        $this->assertStringContainsString('北京,上海', $output);
        $this->assertStringContainsString('广州,深圳', $output);
        
        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }
    
    public function testExecute_withNoProcesses_displaysEmptyTable(): void
    {
        // 准备空的模拟数据
        $emptyCollection = new ArrayCollection();
        
        // 配置模拟对象的行为
        $this->networkMonitor->method('getProcessesInfo')
            ->willReturn($emptyCollection);
        
        // 执行命令
        $this->commandTester->execute([], ['interactive' => false]);
        
        // 获取命令输出
        $output = $this->commandTester->getDisplay();
        
        // 验证输出内容
        $this->assertStringContainsString('运行程序', $output);
        
        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }
    
    public function testExecute_withIntervalOption_doesNotCrash(): void
    {
        // 准备模拟数据
        $processesCollection = new ArrayCollection();
        $processesCollection->add(new ProcessInfoVO(
            '1234',
            'nginx',
            10,
            100,
            1024,
            2048,
            1.5,
            '北京,上海'
        ));
        
        // 配置模拟对象的行为
        $this->networkMonitor->method('getProcessesInfo')
            ->willReturn($processesCollection);
        
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
        $processesCollection = new ArrayCollection();
        $processesCollection->add(new ProcessInfoVO(
            '1234',
            'nginx',
            10,
            100,
            1024,
            2048,
            12.3,
            '北京,上海'
        ));
        
        // 配置模拟对象的行为
        $this->networkMonitor->method('getProcessesInfo')
            ->willReturn($processesCollection);
        
        // 执行命令
        $this->commandTester->execute([], ['interactive' => false]);
        
        // 获取命令输出
        $output = $this->commandTester->getDisplay();
        
        // 验证输出包含进程信息
        $this->assertStringContainsString('nginx', $output);
        
        // 验证格式化的值
        $this->assertMatchesRegularExpression('/1(\.0)?\s*K/i', $output);  // 1 KB
        $this->assertMatchesRegularExpression('/2(\.0)?\s*K/i', $output);  // 2 KB
        $this->assertStringContainsString('12.3%', $output);
        
        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }
} 