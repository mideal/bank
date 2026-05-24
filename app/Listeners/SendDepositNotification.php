<?php

namespace App\Listeners;

use App\Events\MoneyDeposited;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendDepositNotification implements ShouldQueue
{
    public function handle(MoneyDeposited $event): void
    {
        $transaction = Transaction::find($event->transactionId);
        $account = Account::find($event->accountId);

        // Notify account owner about deposit
    }
}
