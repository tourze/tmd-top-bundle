<?php

namespace Tourze\TmdTopBundle\Tests\Unit\Adapter;

use PHPUnit\Framework\TestCase;

/**
 * 测试 AbstractAdapter 的具体实现
 */
class AbstractAdapterTest extends TestCase
{
    private TestAbstractAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new TestAbstractAdapter();
    }

    /**
     * 测试私有IP检测 - 测试私有IP地址
     */
    public function testIsPrivateIp_withPrivateIp_returnsTrue(): void
    {
        // 测试常见的私有IP地址
        $privateIps = [
            '192.168.1.1',
            '10.0.0.1',
            '172.16.0.1',
            '127.0.0.1',
        ];

        foreach ($privateIps as $ip) {
            $this->assertTrue(
                $this->adapter->publicIsPrivateIp($ip),
                "IP $ip should be identified as private"
            );
        }
    }

    /**
     * 测试私有IP检测 - 测试公共IP地址
     */
    public function testIsPrivateIp_withPublicIp_returnsFalse(): void
    {
        // 测试公共IP地址
        $publicIps = [
            '8.8.8.8',
            '1.1.1.1',
            '223.5.5.5',
        ];

        foreach ($publicIps as $ip) {
            $this->assertFalse(
                $this->adapter->publicIsPrivateIp($ip),
                "IP $ip should be identified as public"
            );
        }
    }

    /**
     * 测试字节转KB格式化 - 整数KB
     */
    public function testFormatBytesToKB_withWholeKilobytes(): void
    {
        $this->assertEquals('1 KB', $this->adapter->publicFormatBytesToKB(1024));
        $this->assertEquals('2 KB', $this->adapter->publicFormatBytesToKB(2048));
        $this->assertEquals('10 KB', $this->adapter->publicFormatBytesToKB(10240));
    }

    /**
     * 测试字节转KB格式化 - 小数KB
     */
    public function testFormatBytesToKB_withFractionalKilobytes(): void
    {
        $this->assertEquals('1.5 KB', $this->adapter->publicFormatBytesToKB(1536));
        $this->assertEquals('0.5 KB', $this->adapter->publicFormatBytesToKB(512));
        $this->assertEquals('2.25 KB', $this->adapter->publicFormatBytesToKB(2304));
    }

    /**
     * 测试字节转KB格式化 - 边界值
     */
    public function testFormatBytesToKB_withBoundaryValues(): void
    {
        $this->assertEquals('0 KB', $this->adapter->publicFormatBytesToKB(0));
        $this->assertEquals('0 KB', $this->adapter->publicFormatBytesToKB(1));
        $this->assertEquals('0.98 KB', $this->adapter->publicFormatBytesToKB(1000));
    }

    /**
     * 测试执行命令 - 成功的命令
     */
    public function testExecuteCommand_withSuccessfulCommand(): void
    {
        // 使用一个简单的、跨平台的命令
        $result = $this->adapter->publicExecuteCommand('echo "test"');
        
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('test', $result[0]);
    }

    /**
     * 测试执行命令 - 失败的命令
     */
    public function testExecuteCommand_withFailedCommand(): void
    {
        // 使用一个不存在的命令
        $result = $this->adapter->publicExecuteCommand('nonexistentcommand123456');
        
        $this->assertEmpty($result);
    }

    /**
     * 测试接口实现 - 确保所有接口方法都被实现
     */
    public function testInterfaceImplementation(): void
    {
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->adapter->getNetcardInfo());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->adapter->getServicesInfo());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->adapter->getConnectionsInfo());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->adapter->getProcessesInfo());
        $this->assertInstanceOf(\Tourze\TmdTopBundle\VO\ProcessResourceUsageVO::class, $this->adapter->getProcessResourceUsage('test'));
    }
}