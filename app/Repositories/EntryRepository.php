<?php

namespace App\Repositories;

use App\Models\Entry;

class EntryRepository
{
    public function create(int $transactionId, int $accountId, string $amount, string $balanceAfter): Entry
    {
        $entry = new Entry;
        $entry->transaction_id = $transactionId;
        $entry->account_id = $accountId;
        $entry->amount = $amount;
        $entry->balance_after = $balanceAfter;
        $entry->save();

        return $entry;
    }
}
