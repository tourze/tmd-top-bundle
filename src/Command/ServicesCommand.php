<?php

namespace Tourze\TmdTopBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TmdTopBundle\Service\NetworkMonitor;

#[AsCommand(
    name: 'tmd:top:services',
    description: '显示当前系统上运行的服务',
)]
class ServicesCommand extends Command
{
    public function __construct(
        private readonly NetworkMonitor $networkMonitor
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('此命令显示当前系统上运行的服务，包括PID、服务名、IP、端口、连接数、上传下载速率、CPU和内存使用情况');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('监听服务');

        // 获取服务信息
        $services = $this->networkMonitor->getServicesInfo();

        // 输出表格
        $io->table(
            ['PID', '服务', 'IP', '端口', 'IP数', '连接数', '上传', '下载', 'CPU', '内存'],
            $services
        );

        return Command::SUCCESS;
    }
}
