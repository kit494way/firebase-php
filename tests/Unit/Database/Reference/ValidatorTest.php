<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\Database\Reference;

use GuzzleHttp\Psr7\Uri;
use Kreait\Firebase\Database\Reference\Validator;
use Kreait\Firebase\Exception\InvalidArgumentException;
use Kreait\Firebase\Tests\UnitTestCase;
use Psr\Http\Message\UriInterface;

/**
 * @internal
 */
class ValidatorTest extends UnitTestCase
{
    /** @var UriInterface */
    private $uri;

    /** @var Validator */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = new Uri('http://domain.tld');
        $this->validator = new Validator();
    }

    public function testValidateDepth(): void
    {
        $uri = $this->uri->withPath(\implode('/', \array_fill(0, 33, 'x')));

        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateUri($uri);
    }

    public function testValidateKeySize(): void
    {
        $uri = $this->uri->withPath(\str_pad('', 769, 'x'));

        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateUri($uri);
    }

    /**
     * @dataProvider invalidChars
     */
    public function testValidateChar(string $char): void
    {
        $uri = $this->uri->withPath($char);

        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateUri($uri);
    }

    public function invalidChars(): array
    {
        return [
            ['.'],
            ['$'],
            ['#'],
            ['['],
            [']'],
        ];
    }
}
