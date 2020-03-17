<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Tests\UnitTestCase;
use Throwable;

/**
 * @internal
 * @group Firestore
 */
final class FactoryForFirestoreTest extends UnitTestCase
{
    protected function setUp(): void
    {
        self::onlyIfFirestoreIsAvailable();
    }

    public function testCreateFirestoreFromServiceAccountWithFilePath(): void
    {
        (new Factory())
            ->withServiceAccount(self::$fixturesDir.'/ServiceAccount/valid.json')
            ->createFirestore();

        $this->addToAssertionCount(1);
    }

    public function testCreateFirestoreFromServiceAccountAsArray(): void
    {
        $serviceAccount = \json_decode((string) \file_get_contents(self::$fixturesDir.'/ServiceAccount/valid.json'),
            true, 512, \JSON_THROW_ON_ERROR);

        (new Factory())
            ->withServiceAccount($serviceAccount)
            ->createFirestore();

        $this->addToAssertionCount(1);
    }

    public function testCreateFirestoreWithApplicationDefaultCredentials(): void
    {
        \putenv('GOOGLE_APPLICATION_CREDENTIALS='.self::$fixturesDir.'/ServiceAccount/valid.json');

        try {
            (new Factory())->withDisabledAutoDiscovery()->createFirestore();
            $this->addToAssertionCount(1);
        } catch (Throwable $e) {
            $this->fail('A Firestore instance should have been created');
        } finally {
            \putenv('GOOGLE_APPLICATION_CREDENTIALS');
        }
    }
}
