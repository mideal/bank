<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $amount
 * @property TransactionType $type
 * @property TransactionStatus $status
 * @property string|null $idempotency_key
 * @property string|null $description
 */
class Transaction extends Model
{
    protected $fillable = ['amount', 'type', 'status', 'idempotency_key', 'description'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
        ];
    }

    /** @return HasMany<Entry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }
}
