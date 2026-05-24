<?php

namespace App\ValueObjects;

final class Money
{
    /** @var numeric-string */
    public readonly string $amount;

    public function __construct(string $amount)
    {
        $this->amount = $this->validated($amount);
    }

    public function add(self $other): self
    {
        return new self(bcadd($this->amount, $other->amount, 2));
    }

    public function subtract(self $other): self
    {
        return new self(bcsub($this->amount, $other->amount, 2));
    }

    public function isLessThan(self $other): bool
    {
        return bccomp($this->amount, $other->amount, 2) < 0;
    }

    /** @return numeric-string */
    private function validated(string $amount): string
    {
        if (! is_numeric($amount)) {
            throw new \InvalidArgumentException("Amount must be numeric: {$amount}");
        }

        return $amount;
    }
}
