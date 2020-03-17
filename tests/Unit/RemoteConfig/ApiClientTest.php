<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\RemoteConfig;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Kreait\Firebase\Exception\RemoteConfig\OperationAborted;
use Kreait\Firebase\Exception\RemoteConfig\PermissionDenied;
use Kreait\Firebase\Exception\RemoteConfig\RemoteConfigError;
use Kreait\Firebase\Exception\RemoteConfigException;
use Kreait\Firebase\RemoteConfig\ApiClient;
use Kreait\Firebase\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
class ApiClientTest extends UnitTestCase
{
    /** @var ClientInterface|MockObject */
    private $http;

    /** @var ApiClient */
    private $client;

    protected function setUp(): void
    {
        $this->http = $this->createMock(ClientInterface::class);
        $this->client = new ApiClient($this->http);
    }

    /**
     * @param class-string<\Kreait\Firebase\Exception\RemoteConfigException> $expectedClass
     *
     * @dataProvider requestExceptions
     */
    public function testCatchRequestException(RequestException $requestException, string $expectedClass): void
    {
        $this->http->method('request')->willThrowException($requestException);

        $this->expectException($expectedClass);
        $this->client->getTemplate();
    }

    public function testCatchThrowable(): void
    {
        $this->http->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception());

        $this->expectException(RemoteConfigException::class);

        $this->client->getTemplate();
    }

    public function requestExceptions(): array
    {
        $request = new Request('GET', 'http://example.com');

        return [
            [
                new RequestException('Bad Request', $request, new Response(400, [], '{"error":{"message":"ABORTED"}}')),
                OperationAborted::class,
            ],
            [
                new RequestException('Bad Request', $request, new Response(400, [], '{"error":{"message":"PERMISSION_DENIED"}}')),
                PermissionDenied::class,
            ],
            [
                new RequestException('Forbidden', $request, new Response(403, [], '{"error":{"message":"UNKOWN"}}')),
                RemoteConfigError::class,
            ],
        ];
    }
}
