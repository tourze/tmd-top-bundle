<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests\VO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\TmdTopBundle\VO\NetcardInfoVO;

/**
 * @internal
 */
#[CoversClass(NetcardInfoVO::class)]
final class NetcardInfoVOTest extends TestCase
{
    public function testConstructor(): void
    {
        $name = 'eth0';
        $uploadBytes = 1024;
        $downloadBytes = 2048;

        $vo = new NetcardInfoVO($name, $uploadBytes, $downloadBytes);

        $this->assertSame($name, $vo->getName());
        $this->assertSame($uploadBytes, $vo->getUploadBytes());
        $this->assertSame($downloadBytes, $vo->getDownloadBytes());
    }

    public function testFromArray(): void
    {
        $data = [
            'eth0',
            1024,
            2048,
        ];

        $vo = NetcardInfoVO::fromArray($data);

        $this->assertSame($data[0], $vo->getName());
        $this->assertSame($data[1], $vo->getUploadBytes());
        $this->assertSame($data[2], $vo->getDownloadBytes());
    }

    public function testToArray(): void
    {
        $name = 'eth0';
        $uploadBytes = 1024;
        $downloadBytes = 2048;

        $vo = new NetcardInfoVO($name, $uploadBytes, $downloadBytes);
        $array = $vo->toArray();

        $this->assertSame([
            $name,
            $uploadBytes,
            $downloadBytes,
        ], $array);
    }

    public function testFromArrayWithMissingKeys(): void
    {
        $data = [
            'eth0',
        ];

        $vo = NetcardInfoVO::fromArray($data);

        $this->assertSame($data[0], $vo->getName());
        $this->assertSame(0, $vo->getUploadBytes());
        $this->assertSame(0, $vo->getDownloadBytes());
    }
}
