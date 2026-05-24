<?php

namespace App\Events;

use App\Contracts\DomainEvent;
use Illuminate\Foundation\Events\Dispatchable;

final class MoneyTransferred implements DomainEvent
{
    use Dispatchable;

    public function __construct(
        public readonly int $transactionId,
        public readonly int $fromAccountId,
        public readonly int $toAccountId,
    ) {}

    public function toPayload(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'from_account_id' => $this->fromAccountId,
            'to_account_id' => $this->toAccountId,
        ];
    }

    public static function fromPayload(array $payload): static
    {
        return new self(
            $payload['transaction_id'],
            $payload['from_account_id'],
            $payload['to_account_id'],
        );
    }

    public function aggregateId(): int
    {
        return $this->fromAccountId;
    }
}
