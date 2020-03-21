<?php

declare(strict_types=1);

namespace Kreait\Firebase\Auth;

use Firebase\Auth\Token\Domain\Generator;
use Kreait\Firebase\Exception\LogicException;
use Lcobucci\JWT\Token;

final class NonFunctionalLegacyCustomTokenGenerator implements Generator
{
    /** @var string */
    private $reason;

    public function __construct(string $reason)
    {
        $this->reason = $reason;
    }

    public function createCustomToken($uid, array $claims = []): Token
    {
        throw new LogicException($this->reason);
    }
}
