<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Tourze\TmdTopBundle\Service\NetworkMonitorInterface;

#[AsCommand(
    name: self::NAME,
    description: self::DESCRIPTION
)]
class ProcessesCommand extends AbstractRealtimeCommand
{
    public const NAME = 'tmd-top:processes';
    public const DESCRIPTION = '显示当前运行的程序';

    public function __construct(private readonly NetworkMonitorInterface $networkMonitor)
    {
        parent::__construct();
    }

    protected function configureCommand(): void
    {
        $this->setHelp('此命令显示当前运行的程序，包括PID、名称、IP数、连接数、上传下载、CPU占用和地区信息');
    }

    protected function getDisplayTitle(): string
    {
        return '运行程序';
    }

    /**
     * @param array{header: ConsoleSectionOutput, table: ConsoleSectionOutput} $sections
     */
    protected function displayData(InputInterface $input, array $sections): void
    {
        // 获取进程信息
        $processCollection = $this->networkMonitor->getProcessesInfo();

        // 清除并重新渲染表格部分
        $sections['table']->clear();
        $table = new Table($sections['table']);
        $table->setHeaders(['PID', '名称', 'IP数', '连接数', '上传速率', '下载速率', 'CPU占用', '地区']);

        foreach ($processCollection as $process) {
            $table->addRow([
                $process->getPid(),
                $process->getName(),
                $process->getIpCount(),
                $process->getConnectionCount(),
                $this->formatBytes($process->getUploadBytes()),
                $this->formatBytes($process->getDownloadBytes()),
                $this->formatPercentage($process->getCpuUsage()),
                $process->getRegion(),
            ]);
        }

        $table->render();
    }
}
