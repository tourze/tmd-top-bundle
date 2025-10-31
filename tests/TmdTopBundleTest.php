<?php

declare(strict_types=1);

namespace TmdTopBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\TmdTopBundle\TmdTopBundle;

/**
 * @internal
 */
#[CoversClass(TmdTopBundle::class)]
#[RunTestsInSeparateProcesses]
final class TmdTopBundleTest extends AbstractBundleTestCase
{
}
