<?php

declare(strict_types=1);

namespace Kreait\Firebase\Value;

use Kreait\Firebase\Exception\InvalidArgumentException;
use Kreait\Firebase\Value;

class ProjectId implements \JsonSerializable, Value
{
    /** @var string */
    private $value;

    /**
     * @internal
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function sanitizedValue(): string
    {
        if ($sanitizedValue = \preg_replace('/[^A-Za-z0-9\-]/', '-', $this->value)) {
            return $sanitizedValue;
        }

        return $this->value;
    }

    public function __toString()
    {
        return $this->value;
    }

    public function jsonSerialize()
    {
        return $this->value;
    }

    public function equalsTo($other): bool
    {
        return $this->value === (string) $other;
    }
}
