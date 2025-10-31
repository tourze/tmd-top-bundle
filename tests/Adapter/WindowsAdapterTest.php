<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests\Adapter;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\Adapter\WindowsAdapter;
use Tourze\TmdTopBundle\Tests\Adapter\TestableWindowsAdapter;
use Tourze\TmdTopBundle\VO\ProcessResourceUsageVO;

/**
 * @internal
 */
#[CoversClass(WindowsAdapter::class)]
final class WindowsAdapterTest extends TestCase
{
    private TestableWindowsAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = new TestableWindowsAdapter();
    }

    public function testGetNetcardInfo(): void
    {
        $result = $this->adapter->getNetcardInfo();

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function testGetServicesInfo(): void
    {
        $result = $this->adapter->getServicesInfo();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);

        // 检查第一个服务
        $firstService = $result->first();
        $this->assertNotFalse($firstService);
        $this->assertSame('1234', $firstService->getPid());
        $this->assertSame('nginx.exe', $firstService->getServiceName());
        $this->assertSame('*', $firstService->getIp());
        $this->assertSame('80', $firstService->getPort());
        $this->assertGreaterThanOrEqual(0, $firstService->getConnectionCount());
        $this->assertGreaterThanOrEqual(0, $firstService->getIpCount());
    }

    public function testGetConnectionsInfo(): void
    {
        $result = $this->adapter->getConnectionsInfo();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);

        // 检查第一个连接
        $firstConnection = $result->first();
        $this->assertNotFalse($firstConnection);
        $this->assertSame('203.0.113.1', $firstConnection->getRemoteIp());
        $this->assertSame('54321', $firstConnection->getRemotePort());
        $this->assertGreaterThanOrEqual(0, $firstConnection->getUploadBytes());
        $this->assertGreaterThanOrEqual(0, $firstConnection->getDownloadBytes());
        $this->assertSame('未知', $firstConnection->getLocation());
    }

    public function testGetProcessesInfo(): void
    {
        $result = $this->adapter->getProcessesInfo();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);

        // 检查第一个进程
        $firstProcess = $result->first();
        $this->assertNotFalse($firstProcess);
        $this->assertSame('1234', $firstProcess->getPid());
        $this->assertSame('nginx.exe', $firstProcess->getName());
        $this->assertGreaterThanOrEqual(0, $firstProcess->getIpCount());
        $this->assertGreaterThanOrEqual(0, $firstProcess->getConnectionCount());
        $this->assertGreaterThanOrEqual(0, $firstProcess->getUploadBytes());
        $this->assertGreaterThanOrEqual(0, $firstProcess->getDownloadBytes());
        $this->assertGreaterThanOrEqual(0.0, $firstProcess->getCpuUsage());
        $this->assertSame('其他', $firstProcess->getRegion());
    }

    public function testGetProcessResourceUsage(): void
    {
        $result = $this->adapter->getProcessResourceUsage('1234');

        $this->assertInstanceOf(ProcessResourceUsageVO::class, $result);
        $this->assertSame(0.025, $result->getCpu());
        $this->assertSame(0.0, $result->getMem());
    }

    public function testGetProcessResourceUsageWithNonExistentPid(): void
    {
        $result = $this->adapter->getProcessResourceUsage('999999');

        $this->assertInstanceOf(ProcessResourceUsageVO::class, $result);
        $this->assertSame(0.0, $result->getCpu());
        $this->assertSame(0.0, $result->getMem());
    }
}
