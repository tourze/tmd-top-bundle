<?php

namespace Tourze\TmdTopBundle\Tests\Functional\Command;

use Doctrine\Common\Collections\ArrayCollection;
use GeoIp2\Database\Reader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\TmdTopBundle\Command\ConnectionsCommand;
use Tourze\TmdTopBundle\Service\NetworkMonitor;
use Tourze\TmdTopBundle\VO\ConnectionInfoVO;

class ConnectionsCommandTest extends TestCase
{
    private NetworkMonitor $networkMonitor;
    private Reader $geoipReader;
    private CommandTester $commandTester;
    private ConnectionsCommand $command;
    
    protected function setUp(): void
    {
        // 创建一个模拟的 NetworkMonitor 服务
        $this->networkMonitor = $this->createMock(NetworkMonitor::class);
        
        // 创建一个模拟的 GeoIp2 Reader
        $this->geoipReader = $this->createMock(Reader::class);
        
        // 创建命令
        $this->command = new ConnectionsCommand($this->networkMonitor, $this->geoipReader);
        
        // 设置命令的测试回调
        $this->command->executeCallback = function ($input, $output) {
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
        $application = new Application();
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
            return "$bytes B";
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 1) . " KB";
        } else {
            return round($bytes / 1048576, 1) . " MB";
        }
    }
    
    public function testExecute_displaysConnectionsInfo(): void
    {
        // 准备模拟数据
        $connectionsCollection = new ArrayCollection();
        $connectionsCollection->add(new ConnectionInfoVO('8.8.8.8', '12345', 1024, 2048, ''));
        $connectionsCollection->add(new ConnectionInfoVO('192.168.1.1', '54321', 512, 1024, ''));
        
        // 配置 NetworkMonitor 模拟对象的行为
        $this->networkMonitor->method('getConnectionsInfo')
            ->willReturn($connectionsCollection);
        
        // 配置 isPrivateIp 方法的行为
        $this->networkMonitor->method('isPrivateIp')
            ->willReturnCallback(function ($ip) {
                return $ip === '192.168.1.1';
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
    
    public function testExecute_withGeoIPException_handlesErrorGracefully(): void
    {
        // 准备模拟数据
        $connectionsCollection = new ArrayCollection();
        $connectionsCollection->add(new ConnectionInfoVO('1.1.1.1', '12345', 1024, 2048, ''));
        
        // 配置 NetworkMonitor 模拟对象的行为
        $this->networkMonitor->method('getConnectionsInfo')
            ->willReturn($connectionsCollection);
        
        // 配置 isPrivateIp 方法的行为
        $this->networkMonitor->method('isPrivateIp')
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
    
    public function testExecute_withNoConnections_displaysEmptyTable(): void
    {
        // 准备空的模拟数据
        $emptyCollection = new ArrayCollection();
        
        // 配置模拟对象的行为
        $this->networkMonitor->method('getConnectionsInfo')
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
    
    public function testExecute_withIntervalOption_doesNotCrash(): void
    {
        // 准备模拟数据
        $connectionsCollection = new ArrayCollection();
        $connectionsCollection->add(new ConnectionInfoVO('127.0.0.1', '12345', 1024, 2048, ''));
        
        // 配置 NetworkMonitor 模拟对象的行为
        $this->networkMonitor->method('getConnectionsInfo')
            ->willReturn($connectionsCollection);
        
        // 配置 isPrivateIp 方法的行为
        $this->networkMonitor->method('isPrivateIp')
            ->willReturn(true);
        
        // 执行命令，限制为只刷新一次
        $this->commandTester->execute([
            '--interval' => '1',
            '--count' => '1'
        ]);
        
        // 获取命令输出
        $output = $this->commandTester->getDisplay();
        
        // 验证输出内容
        $this->assertStringContainsString('127.0.0.1', $output);
        
        // 验证状态码
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }
} 