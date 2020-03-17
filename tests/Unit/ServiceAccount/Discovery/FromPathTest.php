<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\ServiceAccount\Discovery;

use Kreait\Firebase\Exception\ServiceAccountDiscoveryFailed;
use Kreait\Firebase\ServiceAccount\Discovery\FromPath;
use Kreait\Firebase\Tests\UnitTestCase;

/**
 * @internal
 */
class FromPathTest extends UnitTestCase
{
    public function testItWorks(): void
    {
        $discoverer = new FromPath(self::$fixturesDir.'/ServiceAccount/valid.json');
        $discoverer();
        $this->addToAssertionCount(1);
    }

    public function testItFails(): void
    {
        $this->expectException(ServiceAccountDiscoveryFailed::class);

        $discoverer = new FromPath(self::$fixturesDir.'/ServiceAccount/invalid.json');
        $discoverer();
    }
}
