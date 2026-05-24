<?php

declare(strict_types=1);

namespace App\Dto\Account;

class TransferDto
{
    public function __construct(
        public readonly int $toAccountId,
        public readonly string $amount,
        public readonly ?string $idempotencyKey = null,
    ) {}
}
