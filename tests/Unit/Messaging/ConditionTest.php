<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\Messaging;

use Kreait\Firebase\Exception\Messaging\InvalidArgument;
use Kreait\Firebase\Messaging\Condition;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ConditionTest extends TestCase
{
    /**
     * @dataProvider valueProvider
     */
    public function testFromValue(string $expected, string $value): void
    {
        $this->assertSame($expected, Condition::fromValue($value)->value());
    }

    /**
     * @dataProvider invalidValueProvider
     */
    public function testFromInvalidValue(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        Condition::fromValue($value);
    }

    public function testNoMoreThanFiveTopics(): void
    {
        $valid = "'a' in topics && 'b' in topics || 'c' in topics || 'd' in topics || 'e' in topics";
        $invalid = $valid." || 'f' in topics";

        Condition::fromValue($valid);
        $this->addToAssertionCount(1);

        $this->expectException(InvalidArgument::class);
        Condition::fromValue($invalid);
    }

    public function valueProvider(): array
    {
        return [
            ["'dogs' in topics || 'cats' in topics", "'dogs' in topics || 'cats' in topics"],
            ["'dogs' in topics || 'cats' in topics", '"dogs" in topics || "cats" in topics'],
        ];
    }

    public function invalidValueProvider(): array
    {
        return [
            ["'dogs in Topics"],
            ["'dogs in Topics || 'cats' in topics"],
        ];
    }
}
