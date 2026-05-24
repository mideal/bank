<?php

namespace App\Http\Requests\Account;

use App\Dto\Account\TransferDto;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Data;

class TransferRequest extends Data
{
    public function __construct(
        #[Exists('accounts', 'id')]
        public readonly int $to_account_id,

        #[Numeric, GreaterThan(0)]
        public readonly string $amount,
    ) {}

    public function toTransferDto(?string $idempotencyKey): TransferDto
    {
        return new TransferDto(
            toAccountId: $this->to_account_id,
            amount: $this->amount,
            idempotencyKey: $idempotencyKey,
        );
    }
}
