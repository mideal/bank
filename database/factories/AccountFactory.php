<?php

namespace Database\Factories;

use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'balance' => new Money('0.00'),
            'hold' => new Money('0.00'),
            'currency' => 'RUB',
        ];
    }

    public function withBalance(string $amount): static
    {
        return $this->state(['balance' => new Money($amount)]);
    }
}
