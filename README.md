# bank

[![CI](https://github.com/mideal/bank/actions/workflows/ci.yml/badge.svg)](https://github.com/mideal/bank/actions/workflows/ci.yml)
[![Coverage](https://codecov.io/gh/mideal/bank/branch/main/graph/badge.svg)](https://codecov.io/gh/mideal/bank)

Core banking service with double-entry bookkeeping, event-driven architecture, and transactional guarantees.

## Stack

- **PHP 8.4** / **Laravel**
- **PostgreSQL 17**
- **Kafka** (`junges/laravel-kafka`) — async event delivery
- **Laravel Sanctum** — authentication
- **bcmath** — exact decimal arithmetic

## Running

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve

# Outbox worker (runs every 5 seconds via scheduler)
php artisan schedule:work

# Kafka consumers — run each as a separate process
php artisan kafka:consume:deposits
php artisan kafka:consume:transfer-initiated
php artisan kafka:consume:transferred
```

## API

All routes require a Bearer token (`Authorization: Bearer {token}`).

### Deposit

```
POST /api/accounts/{account}/deposit
X-Idempotency-Key: <uuid>

{ "amount": "500.00" }
```

### Transfer

```
POST /api/accounts/{account}/transfer
X-Idempotency-Key: <uuid>

{ "to_account_id": 2, "amount": "200.00" }
```

### Update profile

```
PATCH /api/user

{ "name": "Ivan", "email": "ivan@example.com", "age": 30 }
```

## Database schema

```
users
  id, name, email, password, age, timestamps

accounts
  id, user_id → users [INDEX], balance decimal(15,2), hold decimal(15,2),
  currency char(3), timestamps
  CHECK (balance >= 0)
  CHECK (hold >= 0)
  CHECK (balance >= hold)        ← available balance never goes negative

transactions
  id, amount decimal(15,2), type, status, idempotency_key unique,
  description, timestamps

entries                           ← append-only (no UPDATE / DELETE)
  id, transaction_id → transactions [INDEX],
  account_id → accounts, amount decimal(15,2),
  balance_after decimal(15,2), timestamps
  INDEX (account_id, created_at) ← monthly statement queries

outbox_events
  id, type, payload jsonb, status, attempts, error, processed_at, timestamps
  INDEX (status, id)
```

## Architecture

### Money Value Object

`Account.balance` and `Account.hold` are cast to a `Money` value object via `MoneyCast`. All arithmetic (`add`, `subtract`) uses `bcmath` — no floating-point errors.

Available balance is computed, never stored:

```php
available = balance - hold
```

### Double-entry bookkeeping

Every operation produces records in `entries`. The `balance_after` column stores the running balance at the time of each entry — enabling O(1) statement generation without recalculating from scratch.

| Operation | Entries |
|-----------|---------|
| Deposit +500 | `account=X, amount=+500.00, balance_after=1500.00` |
| Transfer 200 from A to B | `account=A, amount=-200.00, balance_after=800.00` and `account=B, amount=+200.00, balance_after=1200.00` |

The `entries` table is **append-only** — PostgreSQL rules prevent any `UPDATE` or `DELETE`. Corrections are made by creating new entries, never modifying existing ones.

### Transactional Outbox Pattern

Dispatching events directly inside a transaction is unsafe: if Kafka is unavailable after commit, the event is lost — money moved but no notification sent.

The solution: write the event to `outbox_events` atomically within the same DB transaction:

```
db.transaction {
  UPDATE accounts       ← balance updated
  INSERT transactions   ← operation recorded
  INSERT entries        ← ledger entries written
  INSERT outbox_events  ← event persisted atomically
}
```

The `outbox:process` command runs every 5 seconds and publishes pending events to Kafka:

```
outbox:process
  ├─ SELECT pending FOR UPDATE   ← pessimistic lock, one worker per event
  ├─ Topics::forEvent($event)    ← resolves Kafka topic from event type
  ├─ Kafka::publish()            ← partitioned by aggregateId (account ID)
  └─ status = processed
```

| Scenario | Behavior |
|----------|----------|
| Transaction rolled back | No outbox record created — event never sent |
| Kafka down after commit | Record stays `pending`, worker retries on next run |
| Two workers simultaneously | `SELECT FOR UPDATE` — only one processes each event |
| Worker crashed mid-flight | `attempts` increments, status set to `failed` with error message |

### Kafka consumers

Each topic has a dedicated consumer with its own consumer group, allowing independent scaling:

```
bank.money.deposited          → GROUP: bank-deposit-consumer
bank.money.transfer.initiated → GROUP: bank-transfer-consumer
bank.money.transferred        → GROUP: bank-transferred-consumer
```

Messages are partitioned by `account_id` — all events for the same account land on the same partition, preserving order.

Failed messages are routed to a Dead Letter Queue (`*.dlq`) — the consumer logs the error and continues processing without blocking the partition.

### Two-phase transfer

Transfers use a two-phase protocol to avoid lost updates:

**Phase 1** (in `AccountService::transfer`): freeze funds by increasing `hold`. The account balance is untouched — available balance decreases.

**Phase 2** (in `ProcessTransfer` listener, triggered via Kafka): debit `balance`, release `hold`, credit the recipient, record entries.

This ensures funds are reserved immediately while the actual debit happens asynchronously.

### Race condition protection

`AccountRepository::findForUpdate()` uses `SELECT FOR UPDATE`. Without a lock, two concurrent requests can read the same balance and both write their own value — one update is silently lost.

### Deadlock prevention

For transfers A→B and B→A running simultaneously, each process holds one lock and waits for the other — classic deadlock. The fix: always acquire locks in ascending ID order:

```php
[$firstId, $secondId] = $fromId < $toId ? [$fromId, $toId] : [$toId, $fromId];
```

### Idempotency

The `X-Idempotency-Key` header prevents duplicate operations on retry. If a client retries a request with the same key, the server returns the existing transaction instead of creating a new one.

### Database-level constraints

PostgreSQL `CHECK` constraints enforce balance invariants at the database level — a safeguard even if application code has a bug:

```sql
CHECK (balance >= 0)       -- balance never negative
CHECK (hold >= 0)          -- hold never negative
CHECK (balance >= hold)    -- available never negative
```

## Tests

```bash
php artisan test
```

| File | Coverage |
|------|----------|
| `DepositTest` | Deposit: balance, transaction, entry, authorization, validation |
| `TransferTest` | Transfer: balance, transaction, entries, authorization, insufficient funds |
| `TransferDeadlockTest` | Deadlock reproduction with concurrent opposing transfers (requires `pcntl`) |
| `RaceConditionTest` | Lost update reproduction without lock, correctness with `SELECT FOR UPDATE` |
