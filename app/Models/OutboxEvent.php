<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array<string, mixed> $payload
 */
class OutboxEvent extends Model
{
    protected $fillable = ['type', 'payload', 'status', 'attempts', 'error', 'processed_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<OutboxEvent>  $query
     * @return Builder<OutboxEvent>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending')->orderBy('id');
    }

    public function markProcessed(): void
    {
        $this->status = 'processed';
        $this->processed_at = now();
        $this->save();
    }

    public function markFailed(string $error): void
    {
        $this->status = 'failed';
        $this->error = $error;
        $this->save();
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }
}
