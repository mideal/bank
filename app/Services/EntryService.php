<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EntryRepository;

final class EntryService
{
    public function __construct(private readonly EntryRepository $entryRepository) {}

    public function create(int $transactionId, int $accountId, string $amount, string $balanceAfter): void
    {
        $this->entryRepository->create($transactionId, $accountId, $amount, $balanceAfter);
    }
}
