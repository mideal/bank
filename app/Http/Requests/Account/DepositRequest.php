<?php

namespace App\Http\Requests\Account;

use App\Dto\Account\DepositDto;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Data;

class DepositRequest extends Data
{
    public function __construct(
        #[Numeric, GreaterThan(0)]
        public readonly string $amount,
    ) {}

    public function toDepositDto(?string $idempotencyKey): DepositDto
    {
        return new DepositDto(
            amount: $this->amount,
            idempotencyKey: $idempotencyKey,
        );
    }
}
