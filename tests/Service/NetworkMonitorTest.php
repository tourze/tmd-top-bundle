<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests\Service;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\Service\NetworkMonitor;

/**
 * @internal
 */
#[CoversClass(NetworkMonitor::class)]
final class NetworkMonitorTest extends TestCase
{
    private NetworkMonitor $networkMonitor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->networkMonitor = new NetworkMonitor();
    }

    public function testGetNetcardInfoReturnsCollection(): void
    {
        $result = $this->networkMonitor->getNetcardInfo();

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function testGetServicesInfoReturnsCollection(): void
    {
        $result = $this->networkMonitor->getServicesInfo();

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function testGetConnectionsInfoReturnsCollection(): void
    {
        $result = $this->networkMonitor->getConnectionsInfo();

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function testGetProcessesInfoReturnsCollection(): void
    {
        $result = $this->networkMonitor->getProcessesInfo();

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function testIsPrivateIpWithPrivateIps(): void
    {
        // 测试各种私有IP范围
        $this->assertTrue($this->networkMonitor->isPrivateIp('192.168.1.1'));
        $this->assertTrue($this->networkMonitor->isPrivateIp('10.0.0.1'));
        $this->assertTrue($this->networkMonitor->isPrivateIp('172.16.0.1'));
        $this->assertTrue($this->networkMonitor->isPrivateIp('127.0.0.1'));
    }

    public function testIsPrivateIpWithPublicIps(): void
    {
        // 测试公共IP
        $this->assertFalse($this->networkMonitor->isPrivateIp('8.8.8.8'));
        $this->assertFalse($this->networkMonitor->isPrivateIp('1.1.1.1'));
        $this->assertFalse($this->networkMonitor->isPrivateIp('208.67.222.222'));
    }

    public function testIsPrivateIpWithInvalidIps(): void
    {
        // 测试无效IP
        $this->assertTrue($this->networkMonitor->isPrivateIp('invalid-ip'));
        $this->assertTrue($this->networkMonitor->isPrivateIp('256.256.256.256'));
        $this->assertTrue($this->networkMonitor->isPrivateIp(''));
    }
}
