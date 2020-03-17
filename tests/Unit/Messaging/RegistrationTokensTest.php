<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\Messaging;

use InvalidArgumentException;
use Kreait\Firebase\Messaging\RegistrationToken;
use Kreait\Firebase\Messaging\RegistrationTokens;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 */
final class RegistrationTokensTest extends TestCase
{
    /**
     * @test
     *
     * @param mixed $value
     *
     * @dataProvider validValuesWithExpectedCounts
     */
    public function it_can_be_created_from_values(int $expectedCount, $value): void
    {
        $tokens = RegistrationTokens::fromValue($value);

        $this->assertCount($expectedCount, $tokens);
        $this->assertSame(!$expectedCount, $tokens->isEmpty());
    }

    /**
     * @test
     *
     * @param mixed $value
     *
     * @dataProvider invalidValues
     */
    public function it_rejects_invalid_values($value): void
    {
        $this->expectException(InvalidArgumentException::class);
        RegistrationTokens::fromValue($value);
    }

    /**
     * @test
     */
    public function it_returns_strings(): void
    {
        $token = RegistrationToken::fromValue('foo');

        $tokens = RegistrationTokens::fromValue([$token, $token]);
        $this->assertEquals(['foo', 'foo'], $tokens->asStrings());
    }

    public function validValuesWithExpectedCounts(): array
    {
        $foo = RegistrationToken::fromValue('foo');

        return [
            [1, 'foo'],
            [1, $foo],
            [2, new RegistrationTokens($foo, $foo)],
            [2, [$foo, 'bar']],
            [2, [$foo, new stdClass(), 'bar']],
        ];
    }

    public function invalidValues(): array
    {
        return [
            [new stdClass()],
        ];
    }
}
