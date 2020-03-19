<?php

declare(strict_types=1);

namespace Kreait\Firebase\RemoteConfig;

use Kreait\Firebase\Value;

final class UpdateType implements \JsonSerializable, Value
{
    public const UNSPECIFIED = 'REMOTE_CONFIG_UPDATE_TYPE_UNSPECIFIED';
    public const INCREMENTAL_UPDATE = 'INCREMENTAL_UPDATE';
    public const FORCED_UPDATE = 'FORCED_UPDATE';
    public const ROLLBACK = 'ROLLBACK';

    /** @var string */
    private $value;

    private function __construct()
    {
    }

    /**
     * @param string|self $value
     */
    public static function fromValue($value): self
    {
        $new = new self();
        $new->value = (string) $value;

        return $new;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function equalsTo($other): bool
    {
        return $this->value === (string) $other;
    }
}
