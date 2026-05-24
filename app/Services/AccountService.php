<?php

namespace App\Services;

use App\Dto\Account\DepositDto;
use App\Dto\Account\TransferDto;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\MoneyDeposited;
use App\Events\MoneyTransferInitiated;
use App\Exceptions\AccountNotFoundException;
use App\Exceptions\InsufficientFundsException;
use App\Models\Account;
use App\Models\Transaction;
use App\Repositories\AccountRepository;
use App\ValueObjects\Money;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\ConnectionInterface;

final class AccountService
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly TransactionService $transactionService,
        private readonly EntryService $entryService,
        private readonly DomainEventStore $eventStore,
        private readonly ConnectionInterface $db,
    ) {}

    public function findForUpdate(int $id): ?Account
    {
        return $this->accountRepository->findForUpdate($id);
    }

    public function save(Account $account): Account
    {
        return $this->accountRepository->save($account);
    }

    public function deposit(int $accountId, int $userId, DepositDto $dto): Transaction
    {
        return $this->db->transaction(function () use ($accountId, $userId, $dto) {
            if ($dto->idempotencyKey !== null) {
                $existing = $this->transactionService->findByIdempotencyKey($dto->idempotencyKey);
                if ($existing instanceof Transaction) {
                    return $existing;
                }
            }

            $account = $this->accountRepository->findForUpdate($accountId);

            if (! $account instanceof Account) {
                throw new AccountNotFoundException($accountId);
            }

            if ($account->user_id !== $userId) {
                throw new AuthorizationException;
            }

            $amount = new Money($dto->amount);
            $account->balance = $account->balance->add($amount);
            $this->accountRepository->save($account);

            $transaction = $this->transactionService->create(
                amount: $dto->amount,
                type: TransactionType::Deposit,
                idempotencyKey: $dto->idempotencyKey,
            );

            $this->entryService->create($transaction->id, $account->id, $dto->amount, $account->balance->amount);
            $this->eventStore->store(new MoneyDeposited($transaction->id, $account->id));

            return $transaction;
        });
    }

    public function transfer(int $fromAccountId, int $userId, TransferDto $dto): Transaction
    {
        return $this->db->transaction(function () use ($fromAccountId, $userId, $dto) {
            if ($dto->idempotencyKey !== null) {
                $existing = $this->transactionService->findByIdempotencyKey($dto->idempotencyKey);
                if ($existing instanceof Transaction) {
                    return $existing;
                }
            }

            $from = $this->accountRepository->findForUpdate($fromAccountId);

            if (! $from instanceof Account) {
                throw new AccountNotFoundException($fromAccountId);
            }

            if ($from->user_id !== $userId) {
                throw new AuthorizationException;
            }

            $to = $this->accountRepository->findForUpdate($dto->toAccountId);

            if (! $to instanceof Account) {
                throw new AccountNotFoundException($dto->toAccountId);
            }

            $amount = new Money($dto->amount);

            if ($from->available()->isLessThan($amount)) {
                throw new InsufficientFundsException;
            }

            $from->hold = $from->hold->add($amount);
            $this->accountRepository->save($from);

            $transaction = $this->transactionService->create(
                amount: $dto->amount,
                type: TransactionType::Transfer,
                status: TransactionStatus::Pending,
                idempotencyKey: $dto->idempotencyKey,
            );

            $this->eventStore->store(new MoneyTransferInitiated(
                $transaction->id,
                $from->id,
                $to->id,
                $dto->amount,
            ));

            return $transaction;
        });
    }
}
