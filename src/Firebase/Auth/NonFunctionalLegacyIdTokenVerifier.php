<?php

declare(strict_types=1);

namespace Kreait\Firebase\Auth;

use Firebase\Auth\Token\Domain\Verifier;
use Kreait\Firebase\Exception\LogicException;
use Lcobucci\JWT\Token;

final class NonFunctionalLegacyIdTokenVerifier implements Verifier
{
    /** @var string */
    private $reason;

    public function __construct(string $reason)
    {
        $this->reason = $reason;
    }

    public function verifyIdToken($token): Token
    {
        throw new LogicException($this->reason);
    }
}
