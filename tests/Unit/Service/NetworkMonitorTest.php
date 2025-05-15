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
    
    public function testIsPrivateIp_withPrivateIp_returnsTrue(): void
    {
        $privateIps = [
            '10.0.0.1',
            '172.16.0.1',
            '192.168.1.1',
            '127.0.0.1',
            'localhost',
            '::1'
        ];
        
        foreach ($privateIps as $ip) {
            $result = $this->networkMonitor->isPrivateIp($ip);
            $this->assertTrue($result, "IP $ip 应该被识别为私有IP");
        }
    }
    
    public function testIsPrivateIp_withPublicIp_returnsFalse(): void
    {
        $publicIps = [
            '8.8.8.8',
            '1.1.1.1',
            '114.114.114.114',
            '208.67.222.222'
        ];
        
        foreach ($publicIps as $ip) {
            $result = $this->networkMonitor->isPrivateIp($ip);
            $this->assertFalse($result, "IP $ip 应该被识别为公共IP");
        }
    }
} 