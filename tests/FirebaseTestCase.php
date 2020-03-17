<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests;

use Google\Cloud\Firestore\FirestoreClient;
use PHPUnit\Framework\TestCase;

abstract class FirebaseTestCase extends TestCase
{
    /** @var string */
    protected static $fixturesDir = __DIR__.'/_fixtures';

    public static function onlyIfFirestoreIsAvailable(): void
    {
        if (!class_exists(FirestoreClient::class)) {
            self::markTestSkipped(FirestoreClient::class.' is not installed');
        }
    }
}
