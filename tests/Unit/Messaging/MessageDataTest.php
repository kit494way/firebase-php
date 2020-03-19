<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\Messaging;

use InvalidArgumentException;
use Kreait\Firebase\Messaging\MessageData;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class MessageDataTest extends TestCase
{
    /**
     * @test
     * @dataProvider validData
     */
    public function it_accepts_valid_data(array $data): void
    {
        MessageData::fromArray($data);
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     * @dataProvider invalidData
     */
    public function it_rejects_invalid_data(array $data): void
    {
        $this->expectException(InvalidArgumentException::class);
        MessageData::fromArray($data);
    }

    public function validData(): array
    {
        return [
            'integer' => [
                ['key' => 1],
            ],
            'float' => [
                ['key' => 1.23],
            ],
            'true' => [
                ['key' => true],
            ],
            'false' => [
                ['key' => false],
            ],
            'null' => [
                ['key' => null],
            ],
            'object with __toString()' => [
                ['key' => new class() {
                    public function __toString()
                    {
                        return 'value';
                    }
                }],
            ],
        ];
    }

    public function invalidData(): array
    {
        return [
            'nested array' => [
                ['key' => ['sub_key' => 'sub_value']],
            ],
        ];
    }
}
