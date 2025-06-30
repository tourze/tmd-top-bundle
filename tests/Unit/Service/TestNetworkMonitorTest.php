<?php

namespace Tourze\TmdTopBundle\Tests\Unit\Service;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\Adapter\AdapterInterface;
use Tourze\TmdTopBundle\Tests\Unit\Service\TestNetworkMonitor;
use Tourze\TmdTopBundle\VO\ConnectionInfoVO;
use Tourze\TmdTopBundle\VO\NetcardInfoVO;
use Tourze\TmdTopBundle\VO\ProcessInfoVO;
use Tourze\TmdTopBundle\VO\ProcessResourceUsageVO;
use Tourze\TmdTopBundle\VO\ServiceInfoVO;

class TestNetworkMonitorTest extends TestCase
{
    public function testConstructorWithAdapter(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $monitor = new TestNetworkMonitor($adapter);

        $this->assertInstanceOf(TestNetworkMonitor::class, $monitor);
    }

        public function testGetNetcardInfoDelegatestoAdapter(): void
    {
        $expectedCollection = new ArrayCollection([
            new NetcardInfoVO('eth0', 1024, 2048)
        ]);

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())
            ->method('getNetcardInfo')
            ->willReturn($expectedCollection);

        $monitor = new TestNetworkMonitor($adapter);
        $result = $monitor->getNetcardInfo();

        $this->assertSame($expectedCollection, $result);
    }

        public function testGetServicesInfoDelegatestoAdapter(): void
    {
        $expectedCollection = new ArrayCollection([
            new ServiceInfoVO('1234', 'nginx', '0.0.0.0', '80', 5, 10, 1024, 2048, 25.5, 64.2)
        ]);

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())
            ->method('getServicesInfo')
            ->willReturn($expectedCollection);

        $monitor = new TestNetworkMonitor($adapter);
        $result = $monitor->getServicesInfo();

        $this->assertSame($expectedCollection, $result);
    }

        public function testGetConnectionsInfoDelegatestoAdapter(): void
    {
        $expectedCollection = new ArrayCollection([
            new ConnectionInfoVO('192.168.1.1', '8080', 1024, 2048, '中国')
        ]);

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())
            ->method('getConnectionsInfo')
            ->willReturn($expectedCollection);

        $monitor = new TestNetworkMonitor($adapter);
        $result = $monitor->getConnectionsInfo();

        $this->assertSame($expectedCollection, $result);
    }

        public function testGetProcessesInfoDelegatestoAdapter(): void
    {
        $expectedCollection = new ArrayCollection([
            new ProcessInfoVO('1234', 'nginx', 5, 10, 1024, 2048, 25.5, '中国')
        ]);

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())
            ->method('getProcessesInfo')
            ->willReturn($expectedCollection);

        $monitor = new TestNetworkMonitor($adapter);
        $result = $monitor->getProcessesInfo();

        $this->assertSame($expectedCollection, $result);
    }

        public function testGetProcessResourceUsageDelegatestoAdapter(): void
    {
        $expectedUsage = new ProcessResourceUsageVO(25.5, 64.2);

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())
            ->method('getProcessResourceUsage')
            ->with('1234')
            ->willReturn($expectedUsage);

        $monitor = new TestNetworkMonitor($adapter);
        $result = $monitor->getProcessResourceUsage('1234');

        $this->assertSame($expectedUsage, $result);
    }
}
