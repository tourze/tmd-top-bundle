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
class ServicesCommand extends AbstractRealtimeCommand
{
    public const NAME = 'tmd-top:services';
    public const DESCRIPTION = '显示监听服务信息';

    public function __construct(private readonly NetworkMonitorInterface $networkMonitor)
    {
        parent::__construct();
    }

    protected function configureCommand(): void
    {
        $this->setHelp('此命令显示当前系统上运行的服务，包括PID、服务名、IP、端口、连接数、上传下载速率、CPU和内存使用情况');
    }

    protected function getDisplayTitle(): string
    {
        return '监听服务';
    }

    /**
     * @param array{header: ConsoleSectionOutput, table: ConsoleSectionOutput} $sections
     */
    protected function displayData(InputInterface $input, array $sections): void
    {
        $servicesInfo = $this->networkMonitor->getServicesInfo();

        // 清除并重新渲染表格部分
        $sections['table']->clear();
        $table = new Table($sections['table']);
        $table->setHeaders(['PID', '服务名称', 'IP', '端口', 'IP数', '连接数', '上传速率', '下载速率', 'CPU使用率', '内存使用率']);

        foreach ($servicesInfo as $info) {
            $table->addRow([
                $info->getPid(),
                $info->getServiceName(),
                $info->getIp(),
                $info->getPort(),
                $info->getIpCount(),
                $info->getConnectionCount(),
                $this->formatBytes($info->getUploadBytes()),
                $this->formatBytes($info->getDownloadBytes()),
                $this->formatPercentage($info->getCpuUsage()),
                $this->formatPercentage($info->getMemoryUsage()),
            ]);
        }

        $table->render();
    }
}
