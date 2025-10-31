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
class NetcardCommand extends AbstractRealtimeCommand
{
    public const NAME = 'tmd-top:netcard';
    public const DESCRIPTION = '显示网卡信息和流量状态';

    public function __construct(private readonly NetworkMonitorInterface $networkMonitor)
    {
        parent::__construct();
    }

    protected function configureCommand(): void
    {
        $this->setHelp('此命令显示系统中所有网络接口的流量信息，包括上传和下载速率');
    }

    protected function getDisplayTitle(): string
    {
        return '网卡';
    }

    /**
     * @param array{header: ConsoleSectionOutput, table: ConsoleSectionOutput} $sections
     */
    protected function displayData(InputInterface $input, array $sections): void
    {
        // 获取网卡信息
        $netcardCollection = $this->networkMonitor->getNetcardInfo();

        // 清除并重新渲染表格部分
        $sections['table']->clear();
        $table = new Table($sections['table']);
        $table->setHeaders(['网卡名称', '上传速率', '下载速率']);

        foreach ($netcardCollection as $netcard) {
            $table->addRow([
                $netcard->getName(),
                $this->formatBytes($netcard->getUploadBytes()),
                $this->formatBytes($netcard->getDownloadBytes()),
            ]);
        }

        $table->render();
    }
}
