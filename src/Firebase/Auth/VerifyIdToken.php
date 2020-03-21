<?php

declare(strict_types=1);

namespace Kreait\Firebase\Auth;

use Kreait\Firebase\Exception\InvalidArgumentException;
use Lcobucci\JWT\Parser;
use Throwable;

final class VerifyIdToken
{
    /** @var string|null */
    private $projectId;

    /** @var string */
    private $idToken;

    private function __construct()
    {

    }

    public static function fromJwt(string $value): self
    {
        try {
            $token = (new Parser())->parse($value);
        } catch (Throwable $e) {
            throw new InvalidArgumentException('Invalid Json Web Token');
        }

        $instance = new self();
        $instance->idToken = (string) $token;

        return $instance;
    }

    public function forProjectWithId(string $projectId): self
    {
        $instance = new self();
        $instance->projectId = $projectId;

        return $instance;
    }

    public function idToken(): string
    {
        return $this->idToken;
    }

    /**
     * @return string|null
     */
    public function projectId()
    {
        return $this->projectId;
    }
}
