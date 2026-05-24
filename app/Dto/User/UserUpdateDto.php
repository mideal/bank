<?php

namespace App\Dto\User;

use App\Dto\Undefined;

class UserUpdateDto
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
