<?php

declare(strict_types=1);

namespace Kreait\Firebase\Messaging;

use Countable;
use Generator;
use IteratorAggregate;

final class Messages implements Countable, IteratorAggregate
{
    /** @var Message[] */
    private $messages;

    public function __construct(Message ...$messages)
    {
        $this->messages = $messages;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return Generator|Message[]
     */
    public function getIterator()
    {
        yield from $this->messages;
    }

    public function count(): int
    {
        return \is_countable($this->messages) ? \count($this->messages) : 0;
    }
}
