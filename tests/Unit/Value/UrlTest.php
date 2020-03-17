<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\Value;

use Kreait\Firebase\Exception\InvalidArgumentException;
use Kreait\Firebase\Value\Url;
use PHPUnit\Framework\TestCase;

/** @internal */
class UrlTest extends TestCase
{
    /**
     * @param mixed $value
     * @dataProvider validValues
     */
    public function testWithValidValue($value): void
    {
        $url = Url::fromValue($value);

        $check = (string) $value;

        $this->assertSame($check, (string) $url);
        $this->assertSame($check, (string) $url->toUri());
        $this->assertSame($check, $url->jsonSerialize());
        $this->assertTrue($url->equalsTo($check));
    }

    /**
     * @param mixed $value
     * @dataProvider invalidValues
     */
    public function testWithInvalidValue($value): void
    {
        $this->expectException(InvalidArgumentException::class);
        Url::fromValue($value);
    }

    public function validValues(): iterable
    {
        yield ['http://domain.tld'];

        yield [new class() {
            public function __toString()
            {
                return 'https://domain.tld';
            }
        }];
    }

    public function invalidValues(): iterable
    {
        yield ['http:///domain.tld'];
        yield ['http://:80'];
    }
}
