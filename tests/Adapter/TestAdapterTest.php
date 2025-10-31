<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests\Adapter;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\TmdTopBundle\Adapter\AdapterInterface;
use Tourze\TmdTopBundle\VO\ConnectionInfoVO;
use Tourze\TmdTopBundle\VO\NetcardInfoVO;
use Tourze\TmdTopBundle\VO\ProcessInfoVO;
use Tourze\TmdTopBundle\VO\ProcessResourceUsageVO;
use Tourze\TmdTopBundle\VO\ServiceInfoVO;

/**
 * @internal
 */
#[CoversClass(TestAdapter::class)]
#[RunTestsInSeparateProcesses]
final class TestAdapterTest extends AbstractAdapterTestCase
{
    private TestAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new TestAdapter();
    }

    protected function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function testSetAndGetNetcardInfo(): void
    {
        // 准备测试数据
        $netcardCollection = new ArrayCollection();
        $netcardCollection->add(new NetcardInfoVO('eth0', 1000, 2000));

        // 设置测试数据
        $this->adapter->setNetcardInfo($netcardCollection);

        // 获取数据并验证
        $result = $this->adapter->getNetcardInfo();

        $this->assertSame($netcardCollection, $result);
        $this->assertCount(1, $result);

        $item = $result->first();
        $this->assertInstanceOf(NetcardInfoVO::class, $item);
        $this->assertSame('eth0', $item->getName());
        $this->assertSame(1000, $item->getUploadBytes());
        $this->assertSame(2000, $item->getDownloadBytes());
    }

    public function testSetAndGetServicesInfo(): void
    {
        // 准备测试数据
        $servicesCollection = new ArrayCollection();
        $servicesCollection->add(new ServiceInfoVO(
            '1234',
            'nginx',
            '127.0.0.1',
            '80',
            10,
            100,
            1000,
            2000,
            1.5,
            2.5
        ));

        // 设置测试数据
        $this->adapter->setServicesInfo($servicesCollection);

        // 获取数据并验证
        $result = $this->adapter->getServicesInfo();

        $this->assertSame($servicesCollection, $result);
        $this->assertCount(1, $result);

        $item = $result->first();
        $this->assertInstanceOf(ServiceInfoVO::class, $item);
        $this->assertSame('1234', $item->getPid());
        $this->assertSame('nginx', $item->getServiceName());
    }

    public function testSetAndGetConnectionsInfo(): void
    {
        // 准备测试数据
        $connectionsCollection = new ArrayCollection();
        $connectionsCollection->add(new ConnectionInfoVO(
            '192.168.1.1',
            '12345',
            1000,
            2000,
            '北京'
        ));

        // 设置测试数据
        $this->adapter->setConnectionsInfo($connectionsCollection);

        // 获取数据并验证
        $result = $this->adapter->getConnectionsInfo();

        $this->assertSame($connectionsCollection, $result);
        $this->assertCount(1, $result);

        $item = $result->first();
        $this->assertInstanceOf(ConnectionInfoVO::class, $item);
        $this->assertSame('192.168.1.1', $item->getRemoteIp());
        $this->assertSame('12345', $item->getRemotePort());
    }

    public function testSetAndGetProcessesInfo(): void
    {
        // 准备测试数据
        $processesCollection = new ArrayCollection();
        $processesCollection->add(new ProcessInfoVO(
            '1234',
            'nginx',
            10,
            100,
            1000,
            2000,
            1.5,
            '北京,上海'
        ));

        // 设置测试数据
        $this->adapter->setProcessesInfo($processesCollection);

        // 获取数据并验证
        $result = $this->adapter->getProcessesInfo();

        $this->assertSame($processesCollection, $result);
        $this->assertCount(1, $result);

        $item = $result->first();
        $this->assertInstanceOf(ProcessInfoVO::class, $item);
        $this->assertSame('1234', $item->getPid());
        $this->assertSame('nginx', $item->getName());
    }

    public function testSetAndGetProcessResourceUsage(): void
    {
        // 准备测试数据
        $pid = '1234';
        $usage = new ProcessResourceUsageVO(1.5, 2.5);

        // 设置测试数据
        $this->adapter->setProcessResourceUsage($pid, $usage);

        // 获取数据并验证
        $result = $this->adapter->getProcessResourceUsage($pid);

        $this->assertSame($usage, $result);
        $this->assertSame(1.5, $result->getCpu());
        $this->assertSame(2.5, $result->getMem());
    }

    public function testGetProcessResourceUsageWithUnknownPidReturnsDefaultValues(): void
    {
        $result = $this->adapter->getProcessResourceUsage('unknown');

        $this->assertInstanceOf(ProcessResourceUsageVO::class, $result);
        $this->assertSame(0.0, $result->getCpu());
        $this->assertSame(0.0, $result->getMem());
    }
}
