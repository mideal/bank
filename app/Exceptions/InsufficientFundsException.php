<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class InsufficientFundsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Insufficient funds.');
    }
}
