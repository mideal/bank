<?php

namespace App\Listeners;

use App\Events\MoneyTransferred;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendTransferNotification implements ShouldQueue
{
    public function handle(MoneyTransferred $event): void
    {
        $transaction = Transaction::find($event->transactionId);
        $from = Account::find($event->fromAccountId);
        $to = Account::find($event->toAccountId);

        // Notify sender and recipient about transfer
    }
}
