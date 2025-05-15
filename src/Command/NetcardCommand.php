<?php

namespace Tourze\TmdTopBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TmdTopBundle\Service\NetworkMonitor;

#[AsCommand(
    name: 'tmd:top:netcard',
    description: '显示网络接口信息，包括上传和下载速率',
)]
class NetcardCommand extends Command
{
    public function __construct(
        private readonly NetworkMonitor $networkMonitor
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('此命令显示系统中所有网络接口的流量信息，包括上传和下载速率');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('网卡');

        // 获取网卡信息
        $netcards = $this->networkMonitor->getNetcardInfo();

        // 输出表格
        $io->table(
            ['网卡', '上传', '下载'],
            $netcards
        );

        return Command::SUCCESS;
    }
}
