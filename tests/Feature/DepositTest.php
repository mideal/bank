<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\MoneyDeposited;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_returns_401(): void
    {
        $account = Account::factory()->create();

        $this->postJson("/api/accounts/{$account->id}/deposit", ['amount' => '100.00'])
            ->assertStatus(401);
    }

    public function test_deposit_increases_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->withBalance('100.00')->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/accounts/{$account->id}/deposit", ['amount' => '50.00'])
            ->assertOk();

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'balance' => '150.00',
        ]);
    }

    public function test_deposit_creates_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/accounts/{$account->id}/deposit", ['amount' => '200.00'])
            ->assertOk();

        $this->assertDatabaseHas('transactions', [
            'amount' => '200.00',
            'type' => TransactionType::Deposit->value,
            'status' => TransactionStatus::Completed->value,
        ]);
    }

    public function test_deposit_creates_entry(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/accounts/{$account->id}/deposit", ['amount' => '200.00'])
            ->assertOk();

        $this->assertDatabaseHas('entries', [
            'account_id' => $account->id,
            'amount' => '200.00',
        ]);
    }

    public function test_deposit_entry_has_correct_balance_after(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->withBalance('100.00')->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/accounts/{$account->id}/deposit", ['amount' => '50.00'])
            ->assertOk();

        $this->assertDatabaseHas('entries', [
            'account_id' => $account->id,
            'amount' => '50.00',
            'balance_after' => '150.00',
        ]);
    }

    public function test_deposit_is_idempotent(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->withBalance('100.00')->create(['user_id' => $user->id]);
        $key = '550e8400-e29b-41d4-a716-446655440000';

        $first = $this->actingAs($user)
            ->postJson("/api/accounts/{$account->id}/deposit", ['amount' => '50.00'], ['X-Idempotency-Key' => $key])
            ->assertOk()
            ->json('data.id');

        $second = $this->actingAs($user)
            ->postJson("/api/accounts/{$account->id}/deposit", ['amount' => '50.00'], ['X-Idempotency-Key' => $key])
            ->assertOk()
            ->json('data.id');

        $this->assertSame($first, $second);
        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'balance' => '150.00']);
    }

    public function test_deposit_creates_outbox_event(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/accounts/{$account->id}/deposit", ['amount' => '100.00'])
            ->assertOk();

        $this->assertDatabaseHas('outbox_events', [
            'type' => MoneyDeposited::class,
            'status' => 'pending',
        ]);
    }

    public function test_deposit_to_another_users_account_returns_403(): void
    {
        $user = User::factory()->create();
        $other = Account::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/accounts/{$other->id}/deposit", ['amount' => '100.00'])
            ->assertStatus(403);
    }

    public function test_deposit_account_not_found_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/accounts/999/deposit', ['amount' => '100.00'])
            ->assertStatus(404);
    }

    public function test_deposit_amount_must_be_positive(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/accounts/{$account->id}/deposit", ['amount' => '0.00'])
            ->assertStatus(422);
    }

    public function test_deposit_amount_must_be_numeric(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/accounts/{$account->id}/deposit", ['amount' => 'abc'])
            ->assertStatus(422);
    }
}
