<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Kreait\Firebase\Auth\SignIn;
use Kreait\Firebase\Auth\SignIn\FailedToSignIn;
use Kreait\Firebase\Auth\SignIn\GuzzleHandler;
use Kreait\Firebase\Auth\SignInAnonymously;
use Kreait\Firebase\Tests\UnitTestCase;

/**
 * @internal
 */
final class GuzzleHandlerTest extends UnitTestCase
{
    /** @var MockHandler */
    private $httpResponses;

    /** @var SignIn */
    private $action;

    /** @var GuzzleHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->httpResponses = new MockHandler();
        $this->action = SignInAnonymously::new();

        $this->handler = new GuzzleHandler(new Client(['handler' => $this->httpResponses]));
    }

    /**
     * @test
     */
    public function it_fails_on_an_unsupported_action(): void
    {
        $this->expectException(FailedToSignIn::class);
        $this->handler->handle(new class() implements SignIn {
        });
    }

    /**
     * @test
     */
    public function it_fails_when_guzzle_fails(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('send')->willThrowException($this->createMock(ConnectException::class));

        $handler = new GuzzleHandler($client);

        $this->expectException(FailedToSignIn::class);
        $handler->handle($this->action);
    }

    /**
     * @test
     */
    public function it_fails_on_an_unsuccessful_response(): void
    {
        $this->httpResponses->append($response = new Response(400));

        try {
            $this->handler->handle($this->action);
        } catch (FailedToSignIn $e) {
            $this->assertSame($response, $e->response());
            $this->assertSame($this->action, $e->action());
        }
    }

    /**
     * @test
     */
    public function it_fails_on_a_successful_response_with_invalid_json(): void
    {
        $this->httpResponses->append(new Response(200, [], '{'));

        $this->expectException(FailedToSignIn::class);
        $this->handler->handle($this->action);
    }

    /**
     * @test
     */
    public function it_works(): void
    {
        $this->httpResponses->append(new Response(200, [], (string) \json_encode([
            'id_token' => 'id_token',
            'refresh_token' => 'refresh_token',
            'access_token' => 'access_token',
            'expires_in' => 3600,
        ], \JSON_THROW_ON_ERROR | \JSON_FORCE_OBJECT)));

        $this->handler->handle($this->action);
        $this->addToAssertionCount(1);
    }
}
