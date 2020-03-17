<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit;

use Kreait\Firebase\Exception\InvalidArgumentException;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Tests\UnitTestCase;

/**
 * @internal
 */
class ServiceAccountTest extends UnitTestCase
{
    /** @var string */
    private $validJsonFile;

    /** @var string */
    private $realpathedValidJsonFile;

    /** @var string */
    private $invalidJsonFile;

    /** @var string */
    private $malformedJsonFile;

    /** @var string */
    private $symlinkedJsonFile;

    /** @var string */
    private $unreadableJsonFile;

    protected function setUp(): void
    {
        $this->validJsonFile = self::$fixturesDir.'/ServiceAccount/valid.json';
        $this->realpathedValidJsonFile = (string) \realpath($this->validJsonFile);
        $this->malformedJsonFile = self::$fixturesDir.'/ServiceAccount/malformed.json';
        $this->invalidJsonFile = self::$fixturesDir.'/ServiceAccount/invalid.json';
        $this->symlinkedJsonFile = self::$fixturesDir.'/ServiceAccount/symlinked.json';
        $this->unreadableJsonFile = self::$fixturesDir.'/ServiceAccount/unreadable.json';

        @\chmod($this->unreadableJsonFile, 0000);
    }

    protected function tearDown(): void
    {
        @\chmod($this->unreadableJsonFile, 0644);
    }

    public function testGetters(): void
    {
        $serviceAccount = ServiceAccount::fromValue($this->validJsonFile);
        $data = \json_decode((string) \file_get_contents($this->validJsonFile), true, 512, \JSON_THROW_ON_ERROR);

        $this->assertSame($data['project_id'], $serviceAccount->getProjectId());
        $this->assertSame($data['client_id'], $serviceAccount->getClientId());
        $this->assertSame($data['client_email'], $serviceAccount->getClientEmail());
        $this->assertSame($data['private_key'], $serviceAccount->getPrivateKey());
        $this->assertSame($this->validJsonFile, $serviceAccount->getFilePath());
    }

    public function testCreateFromJsonText(): void
    {
        $serviceAccount = ServiceAccount::fromValue((string) \file_get_contents($this->validJsonFile));
        $this->assertNull($serviceAccount->getFilePath());
    }

    public function testCreateFromJsonFile(): void
    {
        $serviceAccount = ServiceAccount::fromValue($this->validJsonFile);
        $this->assertSame($this->validJsonFile, $serviceAccount->getFilePath());
    }

    public function testCreateFromRealpathedJsonFile(): void
    {
        $serviceAccount = ServiceAccount::fromValue($this->realpathedValidJsonFile);
        $this->assertSame($this->realpathedValidJsonFile, $serviceAccount->getFilePath());
    }

    public function testCreateFromSymlinkedJsonFile(): void
    {
        $serviceAccount = ServiceAccount::fromValue($this->symlinkedJsonFile);
        $this->assertSame($this->symlinkedJsonFile, $serviceAccount->getFilePath());
    }

    public function testCreateFromMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ServiceAccount::fromValue('missing.json');
    }

    public function testCreateFromMalformedJsonFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ServiceAccount::fromValue($this->malformedJsonFile);
    }

    public function testCreateFromInvalidJsonFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ServiceAccount::fromValue($this->invalidJsonFile);
    }

    public function testCreateFromDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ServiceAccount::fromValue(__DIR__);
    }

    public function testCreateFromUnreadableFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ServiceAccount::fromValue($this->unreadableJsonFile);
    }

    public function testCreateFromArray(): void
    {
        $data = \json_decode((string) \file_get_contents($this->validJsonFile), true, 512, \JSON_THROW_ON_ERROR);

        $serviceAccount = ServiceAccount::fromValue($data);
        $this->addToAssertionCount(1);
        $this->assertNull($serviceAccount->getFilePath());
    }

    public function testCreateFromServiceAccount(): void
    {
        $serviceAccount = $this->createMock(ServiceAccount::class);

        $this->assertSame($serviceAccount, ServiceAccount::fromValue($serviceAccount));
    }

    public function testCreateFromInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ServiceAccount::fromValue(false);
    }

    public function testCreateWithInvalidClientEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ServiceAccount())->withClientEmail('foo');
    }

    public function testWithCustomDiscoverer(): void
    {
        $expected = $this->createMock(ServiceAccount::class);

        $discoverer = $this->createMock(ServiceAccount\Discoverer::class);
        $discoverer
            ->method('discover')
            ->willReturn($expected);

        $this->assertSame($expected, ServiceAccount::discover($discoverer));
    }

    /**
     * @see https://github.com/kreait/firebase-php/issues/228
     *
     * @dataProvider sanitizableProjectIdProvider
     */
    public function testGetSanitizedProjectId(string $expected, string $given): void
    {
        $serviceAccount = ServiceAccount::fromJsonFile($this->validJsonFile)->withProjectId($given);

        $this->assertSame($given, $serviceAccount->getProjectId());
        $this->assertSame($expected, $serviceAccount->getSanitizedProjectId());
    }

    public function sanitizableProjectIdProvider(): array
    {
        return [
            ['example-com-api-project-xxxxxx', 'example.com:api-project-xxxxxx'],
        ];
    }
}
