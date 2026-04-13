<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class EmailAlreadyTakenException extends RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct("Email '{$email}' is already taken.");
    }
}
