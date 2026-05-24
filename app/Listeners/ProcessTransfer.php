<?php

namespace App\Listeners;

use App\Enums\TransactionStatus;
use App\Events\MoneyTransferInitiated;
use App\Events\MoneyTransferred;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\AccountService;
use App\Services\DomainEventStore;
use App\Services\EntryService;
use App\Services\TransactionService;
use App\ValueObjects\Money;
use Illuminate\Database\ConnectionInterface;

final class ProcessTransfer
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly TransactionService $transactionService,
        private readonly EntryService $entryService,
        private readonly DomainEventStore $eventStore,
        private readonly ConnectionInterface $db,
    ) {}

    public function handle(MoneyTransferInitiated $event): void
    {
        $transaction = $this->transactionService->find($event->transactionId);

        if (! $transaction instanceof Transaction || $transaction->status !== TransactionStatus::Pending) {
            return;
        }

        $this->db->transaction(function () use ($event): void {
            $locked = $this->transactionService->findForUpdate($event->transactionId);
            if (! $locked instanceof Transaction || $locked->status !== TransactionStatus::Pending) {
                return;
            }

            [$firstId, $secondId] = $event->fromAccountId < $event->toAccountId
                ? [$event->fromAccountId, $event->toAccountId]
                : [$event->toAccountId, $event->fromAccountId];

            $first = $this->accountService->findForUpdate($firstId);
            $second = $this->accountService->findForUpdate($secondId);

            if (! $first instanceof Account || ! $second instanceof Account) {
                throw new \RuntimeException("Account not found during transfer {$event->transactionId}");
            }

            $from = $first->id === $event->fromAccountId ? $first : $second;
            $to = $first->id === $event->toAccountId ? $first : $second;

            $amount = new Money($event->amount);

            $from->balance = $from->balance->subtract($amount);
            $from->hold = $from->hold->subtract($amount);
            $to->balance = $to->balance->add($amount);

            $this->accountService->save($from);
            $this->accountService->save($to);

            $this->entryService->create($event->transactionId, $from->id, bcsub('0', $amount->amount, 2), $from->balance->amount);
            $this->entryService->create($event->transactionId, $to->id, $amount->amount, $to->balance->amount);

            $this->transactionService->updateStatus($event->transactionId, TransactionStatus::Completed);

            $this->eventStore->store(new MoneyTransferred(
                $event->transactionId,
                $event->fromAccountId,
                $event->toAccountId,
            ));
        });
    }
}
