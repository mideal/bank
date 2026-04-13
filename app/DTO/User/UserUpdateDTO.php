<?php

namespace App\DTO\User;

use App\DTO\Undefined;

class UserUpdateDTO
{
    public function __construct(
        public string|Undefined $name = new Undefined,
        public string|Undefined $email = new Undefined,
        public int|null|Undefined $age = new Undefined,
    ) {}

    public function fill(): array
    {
        return array_filter(
            get_object_vars($this),
            fn ($value) => ! $value instanceof Undefined
        );
    }
}
