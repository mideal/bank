<?php

namespace App\Repositories;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;

class TransactionRepository
{
    public function find(int $id): ?Transaction
    {
        return Transaction::find($id);
    }

    public function findForUpdate(int $id): ?Transaction
    {
        return Transaction::lockForUpdate()->find($id);
    }

    public function findByIdempotencyKey(string $key): ?Transaction
    {
        return Transaction::where('idempotency_key', $key)->first();
    }

    public function create(
        string $amount,
        TransactionType $type,
        TransactionStatus $status = TransactionStatus::Completed,
        ?string $idempotencyKey = null,
        ?string $description = null,
    ): Transaction {
        $transaction = new Transaction;
        $transaction->amount = $amount;
        $transaction->type = $type;
        $transaction->status = $status;
        $transaction->idempotency_key = $idempotencyKey;
        $transaction->description = $description;
        $transaction->save();

        return $transaction;
    }

    public function updateStatus(int $id, TransactionStatus $status): void
    {
        Transaction::findOrFail($id)->update(['status' => $status->value]);
    }
}
