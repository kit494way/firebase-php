<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\Messaging;

use Kreait\Firebase\Exception\Messaging\InvalidArgument;
use Kreait\Firebase\Messaging\Topic;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class TopicTest extends TestCase
{
    /**
     * @dataProvider valueProvider
     */
    public function testFromValue(string $expected, string $value): void
    {
        $this->assertSame($expected, Topic::fromValue($value)->value());
    }

    /**
     * @dataProvider invalidValueProvider
     */
    public function testFromInvalidValue(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        Topic::fromValue($value);
    }

    public function valueProvider(): array
    {
        return [
            ['foo', 'foo'],
            ['foo', '/foo'],
            ['foo', 'foo/'],
            ['foo', '/topic/foo'],
        ];
    }

    public function invalidValueProvider(): array
    {
        return [
            ['$'],
            ['ä'],
            ['é'],
        ];
    }
}
