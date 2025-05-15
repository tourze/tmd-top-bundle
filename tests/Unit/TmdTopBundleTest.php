<?php

namespace Tourze\TmdTopBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\TmdTopBundle\TmdTopBundle;

class TmdTopBundleTest extends TestCase
{
    public function testIsInstanceOfBundle(): void
    {
        $bundle = new TmdTopBundle();
        
        $this->assertInstanceOf(Bundle::class, $bundle);
    }
} 