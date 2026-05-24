<?php

namespace App\Events;

use App\Contracts\DomainEvent;
use Illuminate\Foundation\Events\Dispatchable;

final class MoneyDeposited implements DomainEvent
{
    use Dispatchable;

    public function __construct(
        public readonly int $transactionId,
        public readonly int $accountId,
    ) {}

    public function toPayload(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'account_id' => $this->accountId,
        ];
    }

    public static function fromPayload(array $payload): static
    {
        return new self($payload['transaction_id'], $payload['account_id']);
    }

    public function aggregateId(): int
    {
        return $this->accountId;
    }
}
