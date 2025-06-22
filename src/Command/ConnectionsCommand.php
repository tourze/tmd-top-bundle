<?php

namespace Tourze\TmdTopBundle\Command;

use ChrisUllyott\FileSize;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TmdTopBundle\Service\NetworkMonitor;

#[AsCommand(
    name: self::NAME,
    description: '显示客户端连接信息',
)]
class ConnectionsCommand extends Command
{
    public const NAME = 'tmd-top:connections';
    
    /**
     * 用于测试的回调，允许在测试中替换 execute 方法
     *
     * @var callable|null
     */
    public $executeCallback = null;

    public function __construct(
        private readonly NetworkMonitor $networkMonitor,
        private readonly Reader $geoipReader,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('此命令显示客户端连接信息，包括客户端IP、端口、上传下载速率和地理位置')
            ->addOption(
                'interval',
                'i',
                InputOption::VALUE_OPTIONAL,
                '刷新间隔时间（秒），设置后将启用实时更新模式',
                null
            )
            ->addOption(
                'count',
                'c',
                InputOption::VALUE_OPTIONAL,
                '更新次数，设置后将在指定次数后退出，默认无限',
                null
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 如果存在测试回调，则使用回调替代原始执行逻辑
        if (is_callable($this->executeCallback)) {
            return call_user_func($this->executeCallback, $input, $output);
        }
        
        $interval = $input->getOption('interval');
        $count = $input->getOption('count');

        $updateCount = 0;
        $isRealtime = $interval !== null;

        // 创建输出分段
        if (!$output instanceof ConsoleOutputInterface) {
            $io = new SymfonyStyle($input, $output);
            $io->error('This command requires an interactive terminal.');
            return Command::FAILURE;
        }
        
        $headerSection = $output->section();
        $tableSection = $output->section();

        do {
            // 显示时间戳
            $timestamp = date('Y-m-d H:i:s');
            $headerSection->clear();
            $io = new SymfonyStyle($input, $headerSection);
            $io->title('连接详情 - ' . $timestamp);

            // 获取连接信息
            $connectionCollection = $this->networkMonitor->getConnectionsInfo();

            // 清除并重新渲染表格部分
            $tableSection->clear();
            $table = new Table($tableSection);
            $table->setHeaders(['客户端IP', '端口', '上传速率', '下载速率', '地区']);

            foreach ($connectionCollection as $connection) {
                $location = $this->getIpLocation($connection->getRemoteIp());

                $table->addRow([
                    $connection->getRemoteIp(),
                    $connection->getRemotePort(),
                    $this->formatBytes($connection->getUploadBytes()),
                    $this->formatBytes($connection->getDownloadBytes()),
                    $location,
                ]);
            }

            $table->render();

            // 在非实时模式下只显示一次
            if (!$isRealtime) {
                break;
            }

            // 刷新计数
            $updateCount++;

            // 如果设置了count选项并且已达到指定次数，则退出
            if ($count !== null && $updateCount >= (int)$count) {
                break;
            }

            // 刷新捕获信号
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            // 等待下一次刷新
            sleep((int)$interval);

        } while ($isRealtime);

        return Command::SUCCESS;
    }

    /**
     * 获取IP地理位置
     */
    private function getIpLocation(string $ip): string
    {
        try {
            if ($this->networkMonitor->isPrivateIp($ip) || $ip === '127.0.0.1') {
                return 'local/local/local';
            }

            $record = $this->geoipReader->city($ip);

            $country = $record->country->names['zh-CN'] ?? 'null';
            $province = $record->mostSpecificSubdivision->names['zh-CN'] ?? 'null';
            $city = $record->city->names['zh-CN'] ?? 'null';

            return "中国/$country/$province";
        } catch (AddressNotFoundException $e) {
            return 'null/null/null';
        } catch (\Throwable $e) {
            return 'null/null/null';
        }
    }

    /**
     * 格式化字节数为人类可读的形式
     */
    private function formatBytes(int $bytes): string
    {
        try {
            return (new FileSize($bytes))->asAuto();
        } catch (\Throwable $e) {
            // 如果 FileSize 库出现问题，提供基本的后备格式化
            if ($bytes < 1024) {
                return "$bytes B";
            } elseif ($bytes < 1048576) {
                return round($bytes / 1024, 2) . " KB";
            } elseif ($bytes < 1073741824) {
                return round($bytes / 1048576, 2) . " MB";
            } else {
                return round($bytes / 1073741824, 2) . " GB";
            }
        }
    }
}
