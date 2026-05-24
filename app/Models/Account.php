<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\ValueObjects\Money;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Money $balance
 * @property Money $hold
 */
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'balance', 'hold', 'currency'];

    protected function casts(): array
    {
        return [
            'balance' => MoneyCast::class,
            'hold' => MoneyCast::class,
        ];
    }

    public function available(): Money
    {
        return $this->balance->subtract($this->hold);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
