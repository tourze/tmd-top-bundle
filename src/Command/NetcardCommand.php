<?php

namespace Tourze\TmdTopBundle\Command;

use ChrisUllyott\FileSize;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TmdTopBundle\Service\NetworkMonitor;

#[AsCommand(
    name: self::NAME,
    description: '显示网卡信息和流量状态',
)]
class NetcardCommand extends Command
{
    public const NAME = 'tmd-top:netcard';
    
    /**
     * 用于测试的回调，允许在测试中替换 execute 方法
     * 
     * @var callable|null
     */
    public $executeCallback = null;

    public function __construct(private readonly NetworkMonitor $networkMonitor)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('此命令显示系统中所有网络接口的流量信息，包括上传和下载速率')
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
        $headerSection = $output->section();
        $tableSection = $output->section();

        do {
            // 显示时间戳
            $timestamp = date('Y-m-d H:i:s');
            $headerSection->clear();
            $io = new SymfonyStyle($input, $headerSection);
            $io->title('网卡 - ' . $timestamp);

            // 获取网卡信息
            $netcardCollection = $this->networkMonitor->getNetcardInfo();

            // 清除并重新渲染表格部分
            $tableSection->clear();
            $table = new Table($tableSection);
            $table->setHeaders(['网卡名称', '上传速率', '下载速率']);

            foreach ($netcardCollection as $netcard) {
                $table->addRow([
                    $netcard->getName(),
                    $this->formatBytes($netcard->getUploadBytes()),
                    $this->formatBytes($netcard->getDownloadBytes()),
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
     * 格式化字节数为人类可读的形式
     */
    private function formatBytes(int $bytes): string
    {
        try {
            return (new FileSize($bytes))->asAuto();
        } catch (\Exception $e) {
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
