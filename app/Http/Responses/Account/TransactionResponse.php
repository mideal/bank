<?php

declare(strict_types=1);

namespace App\Http\Responses\Account;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;

class TransactionResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $amount,
        public readonly TransactionType $type,
        public readonly TransactionStatus $status,
    ) {}
}
