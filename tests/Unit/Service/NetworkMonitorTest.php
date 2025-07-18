<?php

namespace Tourze\TmdTopBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\Service\NetworkMonitor;

class NetworkMonitorTest extends TestCase
{
    private NetworkMonitor $networkMonitor;

    protected function setUp(): void
    {
        $this->networkMonitor = new NetworkMonitor();
    }

    public function testGetNetcardInfo(): void
    {
        $result = $this->networkMonitor->getNetcardInfo();

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $result);
    }

    public function testGetServicesInfo(): void
    {
        $result = $this->networkMonitor->getServicesInfo();

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $result);
    }

    public function testGetConnectionsInfo(): void
    {
        $result = $this->networkMonitor->getConnectionsInfo();

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $result);
    }

    public function testGetProcessesInfo(): void
    {
        $result = $this->networkMonitor->getProcessesInfo();

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $result);
    }

    public function testGetProcessResourceUsage(): void
    {
        $result = $this->networkMonitor->getProcessResourceUsage('1234');

        $this->assertInstanceOf(\Tourze\TmdTopBundle\VO\ProcessResourceUsageVO::class, $result);
    }

    public function testIsPrivateIpWithPrivateIp(): void
    {
        $this->assertTrue($this->networkMonitor->isPrivateIp('192.168.1.1'));
        $this->assertTrue($this->networkMonitor->isPrivateIp('10.0.0.1'));
        $this->assertTrue($this->networkMonitor->isPrivateIp('172.16.0.1'));
        $this->assertTrue($this->networkMonitor->isPrivateIp('127.0.0.1'));
    }

    public function testIsPrivateIpWithPublicIp(): void
    {
        $this->assertFalse($this->networkMonitor->isPrivateIp('8.8.8.8'));
        $this->assertFalse($this->networkMonitor->isPrivateIp('114.114.114.114'));
        $this->assertFalse($this->networkMonitor->isPrivateIp('1.1.1.1'));
    }

    public function testIsPrivateIpWithInvalidIp(): void
    {
        $this->assertTrue($this->networkMonitor->isPrivateIp('invalid-ip'));
        $this->assertTrue($this->networkMonitor->isPrivateIp('999.999.999.999'));
    }
}
