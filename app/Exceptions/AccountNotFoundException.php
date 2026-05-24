<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class AccountNotFoundException extends RuntimeException
{
    public function __construct(int $accountId)
    {
        parent::__construct("Account #{$accountId} not found.");
    }
}
