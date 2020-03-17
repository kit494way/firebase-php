<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit;

use DateTimeImmutable;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Uri;
use Kreait\Clock\FrozenClock;
use Kreait\Firebase\Exception\LogicException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\ServiceAccount\Discoverer;
use Kreait\Firebase\Tests\UnitTestCase;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

/**
 * @internal
 */
class FactoryTest extends UnitTestCase
{
    /** @var ServiceAccount */
    private $serviceAccount;

    /** @var Factory */
    private $factory;

    protected function setUp(): void
    {
        $this->serviceAccount = ServiceAccount::fromJsonFile(self::$fixturesDir.'/ServiceAccount/valid.json');

        $discoverer = $this->createMock(Discoverer::class);
        $discoverer
            ->method('discover')
            ->willReturn($this->serviceAccount);

        $this->factory = (new Factory())->withServiceAccountDiscoverer($discoverer);
    }

    public function testItAcceptsACustomDatabaseUri(): void
    {
        $uri = new Uri('http://domain.tld/');
        $databaseUri = $this->factory->withDatabaseUri($uri)->createDatabase()->getReference()->getUri();

        $this->assertSame($uri->getScheme(), $databaseUri->getScheme());
        $this->assertSame($uri->getHost(), $databaseUri->getHost());
    }

    public function testItAcceptsACustomDefaultStorageBucket(): void
    {
        $storage = $this->factory->withDefaultStorageBucket('foo')->createStorage();

        $this->assertSame('foo', $storage->getBucket()->name());
    }

    public function testItRejectsAnInvalidStorageConfiguration(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Unable to create a StorageClient.*/');

        $this->factory->createStorage(['keyFilePath' => 'foo']);
    }

    public function testItAcceptsAServiceAccount(): void
    {
        $this->factory->withServiceAccount($this->serviceAccount);
        $this->addToAssertionCount(1);
    }

    public function testItAcceptsAClock(): void
    {
        $this->factory->withClock(new FrozenClock(new DateTimeImmutable()));
        $this->addToAssertionCount(1);
    }

    public function testItAcceptsAVerifierCache(): void
    {
        $this->factory->withVerifierCache($this->createMock(CacheInterface::class));
        $this->addToAssertionCount(1);
    }

    public function testItAcceptsACustomHttpClientConfig(): void
    {
        $apiClient = $this->factory->withHttpClientConfig(['key' => 'value'])->createApiClient();

        $this->assertSame('value', $apiClient->getConfig('key'));
    }

    public function testItAcceptsAdditionalHttpClientMiddlewares(): void
    {
        $this->factory->withHttpClientMiddlewares([
            static function (): void {},
            'name' => static function (): void {},
        ])->createApiClient();

        $this->addToAssertionCount(1);
    }

    public function testServiceAccountDiscoveryCanBeDisabled(): void
    {
        $this->expectException(LogicException::class);
        $this->factory->withDisabledAutoDiscovery()->createAuth();
    }

    public function testDynamicLinksCanBeCreatedWithoutADefaultDomain(): void
    {
        $this->factory->createDynamicLinksService();
        $this->addToAssertionCount(1);
    }

    public function testCreateApiClientWithCustomHandlerStack(): void
    {
        $stack = HandlerStack::create();

        $apiClient = $this->factory->createApiClient(['handler' => $stack]);

        $this->assertSame($stack, $apiClient->getConfig('handler'));
    }
}
