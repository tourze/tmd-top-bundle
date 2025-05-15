<?php

namespace Tourze\TmdTopBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TmdTopBundle\Service\NetworkMonitor;

#[AsCommand(
    name: 'tmd:top:processes',
    description: '显示当前运行的程序',
)]
class ProcessesCommand extends Command
{
    public function __construct(
        private readonly NetworkMonitor $networkMonitor
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('此命令显示当前运行的程序，包括PID、名称、IP数、连接数、上传下载、CPU占用和地区信息');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('运行程序');

        // 获取进程信息
        $processes = $this->networkMonitor->getProcessesInfo();

        // 输出表格
        $io->table(
            ['PID', '名称', 'IP数', '连接数', '上传', '下载', 'CPU', '地区'],
            $processes
        );

        return Command::SUCCESS;
    }
}
