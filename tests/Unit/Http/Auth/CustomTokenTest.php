<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\Http\Auth;

use GuzzleHttp\Psr7;
use Kreait\Firebase\Http\Auth\CustomToken;
use Kreait\Firebase\Tests\UnitTestCase;

/**
 * @internal
 */
class CustomTokenTest extends UnitTestCase
{
    /** @var Psr7\Request */
    private $request;

    protected function setUp(): void
    {
        $this->request = new Psr7\Request('GET', 'http://domain.tld');
    }

    /**
     * @param array|null $claims
     *
     * @dataProvider customTokenProvider
     */
    public function testAuthenticateRequest(string $uid, $claims, array $expectedQueryParams): void
    {
        $authenticated = (new CustomToken($uid, $claims))->authenticateRequest($this->request);

        $this->assertNotSame($this->request, $authenticated);

        $queryParams = Psr7\parse_query($authenticated->getUri()->getQuery());

        $this->assertArrayHasKey('auth_variable_override', $queryParams);
        $this->assertJson($queryParams['auth_variable_override']);

        $this->assertEquals($expectedQueryParams,
            \json_decode($queryParams['auth_variable_override'], true, 512, \JSON_THROW_ON_ERROR));
    }

    public function customTokenProvider(): array
    {
        $uid = 'uid';

        $emptyClaims = [];

        $claims = [
            'string' => 'string',
            'int' => 1337,
            'float' => 1337.37,
            'bool_true' => true,
            'bool_false' => false,
            'null' => null,
        ];

        $expectedClaims = [
            'uid' => $uid,
            'string' => 'string',
            'int' => 1337,
            'float' => 1337.37,
            'bool_true' => true,
            'bool_false' => false,
        ];

        return [
            'without_claims' => ['uid', $emptyClaims, ['uid' => $uid]],
            'with_claims' => ['uid', $claims, $expectedClaims],
        ];
    }
}
