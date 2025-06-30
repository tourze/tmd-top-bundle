<?php

namespace Tourze\TmdTopBundle\Tests\Unit\Adapter;

use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\Adapter\WindowsAdapter;

class WindowsAdapterTest extends TestCase
{
    private WindowsAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new WindowsAdapter();
    }

    public function testGetNetcardInfo(): void
    {
        $result = $this->adapter->getNetcardInfo();

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $result);
    }

    public function testGetServicesInfo(): void
    {
        $result = $this->adapter->getServicesInfo();

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $result);
    }

    public function testGetConnectionsInfo(): void
    {
        $result = $this->adapter->getConnectionsInfo();

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $result);
    }

    public function testGetProcessesInfo(): void
    {
        $result = $this->adapter->getProcessesInfo();

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $result);
    }

    public function testGetProcessResourceUsage(): void
    {
        $result = $this->adapter->getProcessResourceUsage('1234');

        $this->assertInstanceOf(\Tourze\TmdTopBundle\VO\ProcessResourceUsageVO::class, $result);
        $this->assertGreaterThanOrEqual(0.0, $result->getCpu());
        $this->assertGreaterThanOrEqual(0.0, $result->getMem());
    }

    public function testGetProcessResourceUsageWithNonExistentPid(): void
    {
        $result = $this->adapter->getProcessResourceUsage('999999');

        $this->assertInstanceOf(\Tourze\TmdTopBundle\VO\ProcessResourceUsageVO::class, $result);
        $this->assertSame(0.0, $result->getCpu());
        $this->assertSame(0.0, $result->getMem());
    }
}
