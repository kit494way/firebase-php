<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\Database\Query\Sorter;

use GuzzleHttp\Psr7\Uri;
use Kreait\Firebase\Database\Query\Sorter\OrderByKey;
use Kreait\Firebase\Tests\UnitTestCase;

/**
 * @internal
 */
class OrderByKeyTest extends UnitTestCase
{
    /** @var OrderByKey */
    protected $sorter;

    protected function setUp(): void
    {
        $this->sorter = new OrderByKey();
    }

    public function testModifyUri(): void
    {
        $this->assertStringContainsString(
            'orderBy='.\rawurlencode('"$key"'),
            (string) $this->sorter->modifyUri(new Uri('http://domain.tld'))
        );
    }

    /**
     * @param mixed $expected
     * @param mixed $value
     *
     * @dataProvider valueProvider
     */
    public function testModifyValue($expected, $value): void
    {
        $this->assertSame($expected, $this->sorter->modifyValue($value));
    }

    public function valueProvider(): array
    {
        return [
            'scalar' => [
                'expected' => 'scalar',
                'given' => 'scalar',
            ],
            'array' => [
                'expected' => [
                    'a' => 'any',
                    'b' => 'any',
                    'c' => 'any',
                    'd' => 'any',
                ],
                'given' => [
                    'c' => 'any',
                    'a' => 'any',
                    'd' => 'any',
                    'b' => 'any',
                ],
            ],
        ];
    }
}
