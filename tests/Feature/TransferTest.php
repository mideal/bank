<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\MoneyTransferInitiated;
use App\Listeners\ProcessTransfer;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_returns_401(): void
    {
        $from = Account::factory()->create();
        $to = Account::factory()->create();

        $this->postJson("/api/accounts/{$from->id}/transfer", [
            'to_account_id' => $to->id,
            'amount' => '100.00',
        ])->assertStatus(401);
    }

    public function test_transfer_returns_202_with_pending_status(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->withBalance('500.00')->create(['user_id' => $user->id]);
        $to = Account::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/accounts/{$from->id}/transfer", [
                'to_account_id' => $to->id,
                'amount' => '200.00',
            ])
            ->assertStatus(202)
            ->assertJsonPath('data.status', TransactionStatus::Pending->value);
    }

    public function test_transfer_freezes_hold_on_initiation(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->withBalance('500.00')->create(['user_id' => $user->id]);
        $to = Account::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/accounts/{$from->id}/transfer", [
                'to_account_id' => $to->id,
                'amount' => '200.00',
            ])->assertStatus(202);

        // Баланс ещё не списан — заморожен через hold
        $this->assertDatabaseHas('accounts', [
            'id' => $from->id,
            'balance' => '500.00',
            'hold' => '200.00',
        ]);
    }

    public function test_transfer_moves_balance_after_processing(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->withBalance('500.00')->create(['user_id' => $user->id]);
        $to = Account::factory()->withBalance('100.00')->create();

        $response = $this->actingAs($user)
            ->postJson("/api/accounts/{$from->id}/transfer", [
                'to_account_id' => $to->id,
                'amount' => '200.00',
            ])->assertStatus(202);

        $transactionId = $response->json('data.id');

        // Симулируем работу outbox воркера
        app(ProcessTransfer::class)->handle(new MoneyTransferInitiated(
            transactionId: $transactionId,
            fromAccountId: $from->id,
            toAccountId: $to->id,
            amount: '200.00',
        ));

        $this->assertDatabaseHas('accounts', ['id' => $from->id, 'balance' => '300.00', 'hold' => '0.00']);
        $this->assertDatabaseHas('accounts', ['id' => $to->id,   'balance' => '300.00']);
    }

    public function test_transfer_creates_entries_after_processing(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->withBalance('500.00')->create(['user_id' => $user->id]);
        $to = Account::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/accounts/{$from->id}/transfer", [
                'to_account_id' => $to->id,
                'amount' => '150.00',
            ])->assertStatus(202);

        app(ProcessTransfer::class)->handle(new MoneyTransferInitiated(
            transactionId: $response->json('data.id'),
            fromAccountId: $from->id,
            toAccountId: $to->id,
            amount: '150.00',
        ));

        $this->assertDatabaseHas('entries', ['account_id' => $from->id, 'amount' => '-150.00']);
        $this->assertDatabaseHas('entries', ['account_id' => $to->id,   'amount' => '150.00']);
    }

    public function test_transfer_marks_transaction_completed_after_processing(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->withBalance('500.00')->create(['user_id' => $user->id]);
        $to = Account::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/accounts/{$from->id}/transfer", [
                'to_account_id' => $to->id,
                'amount' => '150.00',
            ])->assertStatus(202);

        app(ProcessTransfer::class)->handle(new MoneyTransferInitiated(
            transactionId: $response->json('data.id'),
            fromAccountId: $from->id,
            toAccountId: $to->id,
            amount: '150.00',
        ));

        $this->assertDatabaseHas('transactions', [
            'id' => $response->json('data.id'),
            'status' => TransactionStatus::Completed->value,
            'type' => TransactionType::Transfer->value,
        ]);
    }

    public function test_transfer_from_another_users_account_returns_403(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->withBalance('500.00')->create();
        $to = Account::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/accounts/{$from->id}/transfer", [
                'to_account_id' => $to->id,
                'amount' => '100.00',
            ])->assertStatus(403);
    }

    public function test_transfer_from_account_not_found_returns_404(): void
    {
        $user = User::factory()->create();
        $to = Account::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/accounts/999/transfer', [
                'to_account_id' => $to->id,
                'amount' => '100.00',
            ])->assertStatus(404);
    }

    public function test_transfer_to_account_not_found_returns_422(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->withBalance('500.00')->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/accounts/{$from->id}/transfer", [
                'to_account_id' => 999,
                'amount' => '100.00',
            ])->assertStatus(422);
    }

    public function test_transfer_with_insufficient_available_funds_returns_422(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->withBalance('500.00')->create(['user_id' => $user->id]);
        $to = Account::factory()->create();

        // Первый перевод замораживает 400 — available остаётся 100
        $this->actingAs($user)
            ->postJson("/api/accounts/{$from->id}/transfer", [
                'to_account_id' => $to->id,
                'amount' => '400.00',
            ])->assertStatus(202);

        // Второй перевод на 200 — превышает available (100)
        $this->actingAs($user)
            ->postJson("/api/accounts/{$from->id}/transfer", [
                'to_account_id' => $to->id,
                'amount' => '200.00',
            ])->assertStatus(422);
    }

    public function test_transfer_amount_must_be_positive(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->withBalance('500.00')->create(['user_id' => $user->id]);
        $to = Account::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/accounts/{$from->id}/transfer", [
                'to_account_id' => $to->id,
                'amount' => '0.00',
            ])->assertStatus(422);
    }

    public function test_hold_not_set_on_failed_transfer(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->withBalance('50.00')->create(['user_id' => $user->id]);
        $to = Account::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/accounts/{$from->id}/transfer", [
                'to_account_id' => $to->id,
                'amount' => '100.00',
            ])->assertStatus(422);

        $this->assertDatabaseHas('accounts', [
            'id' => $from->id,
            'hold' => '0.00',
        ]);
    }

    public function test_transfer_entries_have_correct_balance_after(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->withBalance('500.00')->create(['user_id' => $user->id]);
        $to = Account::factory()->withBalance('100.00')->create();

        $response = $this->actingAs($user)
            ->postJson("/api/accounts/{$from->id}/transfer", [
                'to_account_id' => $to->id,
                'amount' => '200.00',
            ])->assertStatus(202);

        app(ProcessTransfer::class)->handle(new MoneyTransferInitiated(
            transactionId: $response->json('data.id'),
            fromAccountId: $from->id,
            toAccountId: $to->id,
            amount: '200.00',
        ));

        $this->assertDatabaseHas('entries', [
            'account_id' => $from->id,
            'amount' => '-200.00',
            'balance_after' => '300.00',
        ]);
        $this->assertDatabaseHas('entries', [
            'account_id' => $to->id,
            'amount' => '200.00',
            'balance_after' => '300.00',
        ]);
    }

    public function test_transfer_is_idempotent(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->withBalance('500.00')->create(['user_id' => $user->id]);
        $to = Account::factory()->create();
        $key = '550e8400-e29b-41d4-a716-446655440000';

        $first = $this->actingAs($user)
            ->postJson("/api/accounts/{$from->id}/transfer", [
                'to_account_id' => $to->id,
                'amount' => '200.00',
            ], ['X-Idempotency-Key' => $key])
            ->assertStatus(202)
            ->json('data.id');

        $second = $this->actingAs($user)
            ->postJson("/api/accounts/{$from->id}/transfer", [
                'to_account_id' => $to->id,
                'amount' => '200.00',
            ], ['X-Idempotency-Key' => $key])
            ->assertStatus(202)
            ->json('data.id');

        $this->assertSame($first, $second);
        $this->assertDatabaseHas('accounts', ['id' => $from->id, 'hold' => '200.00']);
    }

    public function test_process_transfer_is_idempotent(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->withBalance('500.00')->create(['user_id' => $user->id]);
        $to = Account::factory()->withBalance('100.00')->create();

        $response = $this->actingAs($user)
            ->postJson("/api/accounts/{$from->id}/transfer", [
                'to_account_id' => $to->id,
                'amount' => '200.00',
            ])->assertStatus(202);

        $event = new MoneyTransferInitiated(
            transactionId: $response->json('data.id'),
            fromAccountId: $from->id,
            toAccountId: $to->id,
            amount: '200.00',
        );

        $listener = app(ProcessTransfer::class);
        $listener->handle($event);
        $listener->handle($event);

        $this->assertDatabaseHas('accounts', ['id' => $from->id, 'balance' => '300.00', 'hold' => '0.00']);
        $this->assertDatabaseHas('accounts', ['id' => $to->id, 'balance' => '300.00']);
    }

    public function test_transfer_creates_outbox_event(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->withBalance('500.00')->create(['user_id' => $user->id]);
        $to = Account::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/accounts/{$from->id}/transfer", [
                'to_account_id' => $to->id,
                'amount' => '200.00',
            ])->assertStatus(202);

        $this->assertDatabaseHas('outbox_events', [
            'type' => MoneyTransferInitiated::class,
            'status' => 'pending',
        ]);
    }
}
