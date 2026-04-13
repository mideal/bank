<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class UserNotFoundException extends RuntimeException
{
    public function __construct(int $userId)
    {
        parent::__construct("User #{$userId} not found.");
    }
}
