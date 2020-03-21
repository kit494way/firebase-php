<?php

declare(strict_types=1);

namespace Kreait\Firebase\Auth\VerifyIdToken;

use Kreait\Firebase\Auth\VerifyIdToken;

interface Handler
{
    public function handle(VerifyIdToken $action);
}
