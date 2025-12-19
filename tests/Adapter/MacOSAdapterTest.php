<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests\Adapter;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\Adapter\MacOSAdapter;
use Tourze\TmdTopBundle\VO\ConnectionInfoVO;
use Tourze\TmdTopBundle\VO\NetcardInfoVO;
use Tourze\TmdTopBundle\VO\ProcessInfoVO;
use Tourze\TmdTopBundle\VO\ProcessResourceUsageVO;
use Tourze\TmdTopBundle\VO\ServiceInfoVO;

/**
 * MacOSAdapter 集成测试 - 仅在 macOS 上运行
 *
 * @internal
 */
#[CoversClass(MacOSAdapter::class)]
#[Group('integration')]
final class MacOSAdapterTest extends TestCase
{
    private MacOSAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        if ('Darwin' !== PHP_OS_FAMILY) {
            self::markTestSkipped('This test only runs on macOS');
        }

        $this->adapter = new MacOSAdapter();
    }

    public function testGetNetcardInfo(): void
    {
        $result = $this->adapter->getNetcardInfo();

        $this->assertInstanceOf(Collection::class, $result);
        // 系统至少有一个网卡
        $this->assertGreaterThanOrEqual(0, $result->count());

        // 如果有网卡，验证数据结构
        if ($result->count() > 0) {
            $firstNetcard = $result->first();
            $this->assertInstanceOf(NetcardInfoVO::class, $firstNetcard);
            $this->assertNotEmpty($firstNetcard->getName());
            $this->assertGreaterThanOrEqual(0, $firstNetcard->getUploadBytes());
            $this->assertGreaterThanOrEqual(0, $firstNetcard->getDownloadBytes());
        }
    }

    public function testGetServicesInfo(): void
    {
        $result = $this->adapter->getServicesInfo();

        $this->assertInstanceOf(Collection::class, $result);
        // 系统可能有监听服务，也可能没有
        $this->assertGreaterThanOrEqual(0, $result->count());

        // 如果有服务，验证数据结构
        if ($result->count() > 0) {
            $firstService = $result->first();
            $this->assertInstanceOf(ServiceInfoVO::class, $firstService);
            $this->assertNotEmpty($firstService->getPid());
            $this->assertNotEmpty($firstService->getPort());
            $this->assertGreaterThanOrEqual(0, $firstService->getConnectionCount());
            $this->assertGreaterThanOrEqual(0, $firstService->getIpCount());
        }
    }

    public function testGetConnectionsInfo(): void
    {
        $result = $this->adapter->getConnectionsInfo();

        $this->assertInstanceOf(Collection::class, $result);
        // 可能有连接，也可能没有
        $this->assertGreaterThanOrEqual(0, $result->count());

        // 如果有连接，验证数据结构
        if ($result->count() > 0) {
            $firstConnection = $result->first();
            $this->assertInstanceOf(ConnectionInfoVO::class, $firstConnection);
            $this->assertNotEmpty($firstConnection->getRemoteIp());
            $this->assertNotEmpty($firstConnection->getRemotePort());
            $this->assertGreaterThanOrEqual(0, $firstConnection->getUploadBytes());
            $this->assertGreaterThanOrEqual(0, $firstConnection->getDownloadBytes());
        }
    }

    public function testGetProcessesInfo(): void
    {
        $result = $this->adapter->getProcessesInfo();

        $this->assertInstanceOf(Collection::class, $result);
        // 可能有进程连接，也可能没有
        $this->assertGreaterThanOrEqual(0, $result->count());

        // 如果有进程，验证数据结构
        if ($result->count() > 0) {
            $firstProcess = $result->first();
            $this->assertInstanceOf(ProcessInfoVO::class, $firstProcess);
            $this->assertNotEmpty($firstProcess->getPid());
            $this->assertGreaterThanOrEqual(0, $firstProcess->getIpCount());
            $this->assertGreaterThanOrEqual(0, $firstProcess->getConnectionCount());
            $this->assertGreaterThanOrEqual(0, $firstProcess->getUploadBytes());
            $this->assertGreaterThanOrEqual(0, $firstProcess->getDownloadBytes());
            $this->assertGreaterThanOrEqual(0.0, $firstProcess->getCpuUsage());
        }
    }

    public function testGetProcessResourceUsage(): void
    {
        // 使用当前进程的 PID 测试
        $currentPid = (string) getmypid();
        $result = $this->adapter->getProcessResourceUsage($currentPid);

        $this->assertInstanceOf(ProcessResourceUsageVO::class, $result);
        $this->assertGreaterThanOrEqual(0.0, $result->getCpu());
        $this->assertGreaterThanOrEqual(0.0, $result->getMem());
    }

    public function testGetProcessResourceUsageWithNonExistentPid(): void
    {
        $result = $this->adapter->getProcessResourceUsage('999999');

        $this->assertInstanceOf(ProcessResourceUsageVO::class, $result);
        // 不存在的进程应返回 0
        $this->assertSame(0.0, $result->getCpu());
        $this->assertSame(0.0, $result->getMem());
    }
}
