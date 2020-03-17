<?php

declare(strict_types=1);

namespace Kreait\Firebase\Messaging;

use Kreait\Firebase\Exception\InvalidArgumentException;
use Throwable;

class Notification implements \JsonSerializable
{
    /** @var string|null */
    private $title;

    /** @var string|null */
    private $body;

    /** @var string|null */
    private $imageUrl;

    private function __construct(string $title = null, string $body = null, string $imageUrl = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->imageUrl = $imageUrl;

        if ($this->title === null && $this->body === null) {
            throw new InvalidArgumentException('The title and body of a notification cannot both be NULL');
        }
    }

    public static function create(string $title = null, string $body = null, string $imageUrl = null): self
    {
        return new self($title, $body, $imageUrl);
    }

    public static function fromArray(array $data): self
    {
        try {
            return new self(
                $data['title'] ?? null,
                $data['body'] ?? null,
                $data['image'] ?? null
            );
        } catch (Throwable $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function withTitle(string $title): self
    {
        $notification = clone $this;
        $notification->title = $title;

        return $notification;
    }

    public function withBody(string $body): self
    {
        $notification = clone $this;
        $notification->body = $body;

        return $notification;
    }

    public function withImageUrl(string $imageUrl): self
    {
        $notification = clone $this;
        $notification->imageUrl = $imageUrl;

        return $notification;
    }

    public function title(): ?string
    {
        return $this->title;
    }

    public function body(): ?string
    {
        return $this->body;
    }

    public function imageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function jsonSerialize(): array
    {
        return \array_filter([
            'title' => $this->title,
            'body' => $this->body,
            'image' => $this->imageUrl,
        ], static function ($value) {
            return $value !== null;
        });
    }
}
