<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Command;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Tourze\TmdTopBundle\Service\NetworkMonitorInterface;

#[AsCommand(
    name: self::NAME,
    description: self::DESCRIPTION
)]
class ConnectionsCommand extends AbstractRealtimeCommand
{
    public const NAME = 'tmd-top:connections';
    public const DESCRIPTION = '显示网络连接信息，支持实时更新模式';

    public function __construct(
        private readonly NetworkMonitorInterface $networkMonitor,
        private readonly string $geoDbPath = '',
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function getDisplayTitle(): string
    {
        return '连接详情';
    }

    /**
     * @param array{header: ConsoleSectionOutput, table: ConsoleSectionOutput} $sections
     */
    protected function displayData(InputInterface $input, array $sections): void
    {
        // 获取连接信息
        $connectionCollection = $this->networkMonitor->getConnectionsInfo();

        // 清除并重新渲染表格部分
        $sections['table']->clear();
        $table = new Table($sections['table']);
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
    }

    /**
     * 获取IP地理位置
     */
    private function getIpLocation(string $ip): string
    {
        if ('' === $this->geoDbPath || !file_exists($this->geoDbPath)) {
            return '未知';
        }

        try {
            $reader = new Reader($this->geoDbPath);
            $record = $reader->city($ip);

            $country = $record->country->names['zh-CN'] ?? $record->country->names['en'] ?? '';
            $city = $record->city->names['zh-CN'] ?? $record->city->names['en'] ?? '';

            return '' !== trim($country . ' ' . $city) ? trim($country . ' ' . $city) : '未知';
        } catch (AddressNotFoundException) {
            return '未知';
        } catch (\Exception) {
            return '未知';
        }
    }
}
