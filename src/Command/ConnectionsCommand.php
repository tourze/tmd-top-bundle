<?php

namespace Tourze\TmdTopBundle\Command;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TmdTopBundle\Service\NetworkMonitor;

#[AsCommand(
    name: 'tmd:top:connections',
    description: '显示客户端连接信息',
)]
class ConnectionsCommand extends Command
{
    public function __construct(
        private readonly NetworkMonitor $networkMonitor,
        private readonly Reader $geoipReader,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('此命令显示客户端连接信息，包括客户端IP、端口、上传下载速率和地理位置');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('详情');

        // 获取连接信息
        $connections = $this->networkMonitor->getConnectionsInfo();

        // 添加地理位置信息
        $connectionsWithLocation = array_map(function ($conn) {
            $conn[4] = $this->getIpLocation($conn[0]);
            return $conn;
        }, $connections);

        // 输出表格
        $io->table(
            ['客户端IP', 'PORT', '上传', '下载', '地区'],
            $connectionsWithLocation
        );

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
        } catch (\Exception $e) {
            return 'null/null/null';
        }
    }
}
