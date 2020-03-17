<?php

declare(strict_types=1);

namespace Kreait\Firebase;

interface Value
{
    /**
     * @param mixed $other
     */
    public function equalsTo($other): bool;
}
