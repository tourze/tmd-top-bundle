<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * VO 对象的测试基类
 *
 * 专为测试 Value Object 相关类设计，避免数据库依赖，提高测试性能
 */
#[CoversClass(AbstractVOTestCase::class)]
abstract class AbstractVOTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // VO 测试基类，不需要特殊设置
    }
}
