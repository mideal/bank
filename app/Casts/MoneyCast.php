<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/** @implements CastsAttributes<Money, Money> */
class MoneyCast implements CastsAttributes
{
    public function get(Model $_model, string $_key, mixed $value, array $_attributes): Money
    {
        if (! \is_string($value)) {
            throw new \UnexpectedValueException(
                'Expected string for money cast, got: '.get_debug_type($value)
            );
        }

        return new Money($value);
    }

    public function set(Model $_model, string $_key, mixed $value, array $_attributes): string
    {
        if (! $value instanceof Money) {
            throw new \UnexpectedValueException(
                'Expected Money for money cast, got: '.get_debug_type($value)
            );
        }

        return $value->amount;
    }
}
