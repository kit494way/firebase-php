<?php

declare(strict_types=1);

namespace Kreait\Firebase\RemoteConfig;

class DefaultValue implements \JsonSerializable
{
    private const IN_APP_DEFAULT_VALUE = true;

    /** @var string|bool */
    private $value;

    private function __construct(?string $value = null)
    {
        $this->value = $value ?: self::IN_APP_DEFAULT_VALUE;
    }

    public static function none(): self
    {
        return new self();
    }

    public static function with(string $value): self
    {
        return new self($value);
    }

    public static function fromArray(array $data): self
    {
        return new self($data['value'] ?? $data['useInAppDefault'] ?? null);
    }

    public function jsonSerialize(): array
    {
        $key = $this->value === true ? 'useInAppDefault' : 'value';

        return [$key => $this->value];
    }
}
