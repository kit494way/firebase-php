<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\Value;

use Kreait\Firebase\Exception\InvalidArgumentException;
use Kreait\Firebase\Value\Uid;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class UidTest extends TestCase
{
    /**
     * @dataProvider validValues
     */
    public function testWithValidValue(string $value): void
    {
        $uid = new Uid($value);

        $this->assertSame($value, (string) $uid);
        $this->assertSame($value, $uid->jsonSerialize());
        $this->assertTrue($uid->equalsTo($value));
    }

    /**
     * @dataProvider invalidValues
     */
    public function testWithInvalidValue(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Uid($value);
    }

    public function validValues(): iterable
    {
        yield ['uid'];
    }

    public function invalidValues(): iterable
    {
        yield [''];
        yield [\str_repeat('x', 129)];
    }
}
