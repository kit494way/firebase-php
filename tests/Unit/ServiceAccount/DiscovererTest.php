<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\ServiceAccount;

use Kreait\Firebase\Exception\ServiceAccountDiscoveryFailed;
use Kreait\Firebase\ServiceAccount\Discoverer;
use Kreait\Firebase\Tests\UnitTestCase;

/**
 * @internal
 */
class DiscovererTest extends UnitTestCase
{
    public function testItHasDefaultMethods(): void
    {
        $discoverer = new Discoverer();
        $property = (new \ReflectionClass($discoverer))->getProperty('methods');
        $property->setAccessible(true);

        $methodCount = \is_countable($property->getValue($discoverer)) ? \count($property->getValue($discoverer)) : 0;

        $this->assertGreaterThan(0, $methodCount);
    }

    public function testItDiscoversAServiceAccount(): void
    {
        $serviceAccount = $this->createServiceAccountMock();

        $method = static function () use ($serviceAccount) {
            return $serviceAccount;
        };

        $discoverer = new Discoverer([$method]);
        $this->assertSame($serviceAccount, $discoverer->discover());
    }

    public function testItFailsWithADistinctException(): void
    {
        $exception = new \Exception('Not found');

        $method = static function () use ($exception): void {
            throw $exception;
        };

        $discoverer = new Discoverer([$method]);

        $this->expectException(ServiceAccountDiscoveryFailed::class);

        $discoverer->discover();
    }
}
