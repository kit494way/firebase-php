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
    protected function setUp()
    {
        self::onlyIfFirestoreIsAvailable();
    }

    public function testCreateFirestoreFromServiceAccountWithFilePath()
    {
        (new Factory())
            ->withServiceAccount(self::$fixturesDir.'/ServiceAccount/valid.json')
            ->createFirestore();

        $this->addToAssertionCount(1);
    }

    public function testCreateFirestoreFromServiceAccountAsArray()
    {
        $serviceAccount = \json_decode((string) \file_get_contents(self::$fixturesDir.'/ServiceAccount/valid.json'), true);

        (new Factory())
            ->withServiceAccount($serviceAccount)
            ->createFirestore();

        $this->addToAssertionCount(1);
    }

    public function testCreateFirestoreWithApplicationDefaultCredentials()
    {
        \putenv('GOOGLE_APPLICATION_CREDENTIALS='.self::$fixturesDir.'/ServiceAccount/valid.json');

        (new Factory())->createFirestore();
        $this->addToAssertionCount(1);

        \putenv('GOOGLE_APPLICATION_CREDENTIALS');
    }

    public function testCreateFirestoreWithFirebaseCredentials()
    {
        \putenv('FIREBASE_CREDENTIALS='.self::$fixturesDir.'/ServiceAccount/valid.json');

        (new Factory())->createFirestore();
        $this->addToAssertionCount(1);

        \putenv('FIREBASE_CREDENTIALS');
    }
}
