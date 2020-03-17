<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\ServiceAccount\Discovery;

use Kreait\Firebase\Exception\ServiceAccountDiscoveryFailed;
use Kreait\Firebase\ServiceAccount\Discovery\FromGoogleWellKnownFile;
use Kreait\Firebase\Tests\UnitTestCase;

/**
 * @internal
 */
class FromGoogleWellKnownFileTest extends UnitTestCase
{
    /** @var string */
    private $backup;

    protected function setUp(): void
    {
        $this->backup = (string) \getenv('HOME');
    }

    protected function tearDown(): void
    {
        \putenv(\sprintf('%s=%s', 'HOME', $this->backup));
    }

    public function testItKnowsWhenTheFileIsInvalid(): void
    {
        $discoverer = new FromGoogleWellKnownFile();

        $this->expectException(ServiceAccountDiscoveryFailed::class);

        \putenv('HOME'); // This will let the Google CredentialsLoader return null
        $discoverer();
    }
}
