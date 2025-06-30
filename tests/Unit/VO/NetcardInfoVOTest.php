<?php

namespace Tourze\TmdTopBundle\Tests\Unit\VO;

use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\VO\NetcardInfoVO;

class NetcardInfoVOTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $vo = new NetcardInfoVO('eth0', 1024, 2048);

        $this->assertSame('eth0', $vo->getName());
        $this->assertSame(1024, $vo->getUploadBytes());
        $this->assertSame(2048, $vo->getDownloadBytes());
    }

    public function testFromArray(): void
    {
        $data = ['wlan0', 512, 1536];
        $vo = NetcardInfoVO::fromArray($data);

        $this->assertSame('wlan0', $vo->getName());
        $this->assertSame(512, $vo->getUploadBytes());
        $this->assertSame(1536, $vo->getDownloadBytes());
    }

    public function testFromArrayWithMissingData(): void
    {
        $data = [];
        $vo = NetcardInfoVO::fromArray($data);

        $this->assertSame('', $vo->getName());
        $this->assertSame(0, $vo->getUploadBytes());
        $this->assertSame(0, $vo->getDownloadBytes());
    }

    public function testFromArrayWithStringNumbers(): void
    {
        $data = ['lo', '256', '768'];
        $vo = NetcardInfoVO::fromArray($data);

        $this->assertSame('lo', $vo->getName());
        $this->assertSame(256, $vo->getUploadBytes());
        $this->assertSame(768, $vo->getDownloadBytes());
    }

    public function testToArray(): void
    {
        $vo = new NetcardInfoVO('enp0s3', 4096, 8192);

        $expected = ['enp0s3', 4096, 8192];

        $this->assertSame($expected, $vo->toArray());
    }
}
