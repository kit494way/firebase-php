<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\Database\Query\Filter;

use GuzzleHttp\Psr7\Uri;
use Kreait\Firebase\Database\Query\Filter\EqualTo;
use Kreait\Firebase\Tests\UnitTestCase;

/**
 * @internal
 */
class EqualToTest extends UnitTestCase
{
    /**
     * @param mixed $given
     *
     * @dataProvider valueProvider
     */
    public function testModifyUri($given, string $expected): void
    {
        $filter = new EqualTo($given);

        $this->assertStringContainsString($expected, (string) $filter->modifyUri(new Uri('http://domain.tld')));
    }

    public function valueProvider(): array
    {
        return [
            [1, 'equalTo=1'],
            ['value', 'equalTo=%22value%22'],
        ];
    }
}
