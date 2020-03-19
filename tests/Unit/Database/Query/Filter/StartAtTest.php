<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\Database\Query\Filter;

use GuzzleHttp\Psr7\Uri;
use Kreait\Firebase\Database\Query\Filter\StartAt;
use Kreait\Firebase\Exception\InvalidArgumentException;
use Kreait\Firebase\Tests\UnitTestCase;

/**
 * @internal
 */
class StartAtTest extends UnitTestCase
{
    public function testCreateWithInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StartAt(null);
    }

    /**
     * @param mixed $given
     * @param string $expected
     *
     * @dataProvider valueProvider
     */
    public function testModifyUri($given, $expected): void
    {
        $filter = new StartAt($given);

        $this->assertStringContainsString($expected, (string) $filter->modifyUri(new Uri('http://domain.tld')));
    }

    public function valueProvider(): array
    {
        return [
            [1, 'startAt=1'],
            ['value', 'startAt=%22value%22'],
        ];
    }
}
