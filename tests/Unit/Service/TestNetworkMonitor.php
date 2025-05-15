<?php

namespace Tourze\TmdTopBundle\Tests\Unit\Service;

use Tourze\TmdTopBundle\Adapter\AdapterInterface;
use Tourze\TmdTopBundle\Service\NetworkMonitor;

/**
 * 可测试的NetworkMonitor子类，允许注入测试适配器
 */
class TestNetworkMonitor extends NetworkMonitor
{
    /**
     * 存储测试适配器的引用
     */
    private AdapterInterface $testAdapter;
    
    /**
     * TestNetworkMonitor 构造函数
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->testAdapter = $adapter;
        // 不调用父类构造函数，以避免创建默认适配器
    }
    
    /**
     * 覆盖父类的createAdapter方法，返回测试适配器
     */
    protected function createAdapter(): AdapterInterface
    {
        return $this->testAdapter;
    }
} 