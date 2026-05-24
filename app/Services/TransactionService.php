<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;

final class TransactionService
{
    public function __construct(private readonly TransactionRepository $transactionRepository) {}

    public function find(int $id): ?Transaction
    {
        return $this->transactionRepository->find($id);
    }

    public function findForUpdate(int $id): ?Transaction
    {
        return $this->transactionRepository->findForUpdate($id);
    }

    public function findByIdempotencyKey(string $key): ?Transaction
    {
        return $this->transactionRepository->findByIdempotencyKey($key);
    }

    public function create(
        string $amount,
        TransactionType $type,
        TransactionStatus $status = TransactionStatus::Completed,
        ?string $idempotencyKey = null,
    ): Transaction {
        return $this->transactionRepository->create(
            amount: $amount,
            type: $type,
            status: $status,
            idempotencyKey: $idempotencyKey,
        );
    }

    public function updateStatus(int $id, TransactionStatus $status): void
    {
        $this->transactionRepository->updateStatus($id, $status);
    }
}
