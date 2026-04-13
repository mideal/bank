<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class UpdateUserData extends Data
{
    public function __construct(
        #[Sometimes, StringType, Max(255)]
        public readonly string|Optional $name,

        #[Sometimes, Email, Unique(table: 'users', column: 'email', ignoreColumn: 'id')]
        public readonly string|Optional $email,

        #[Sometimes, IntegerType, Min(1), Max(150)]
        public readonly int|Optional $age,
    ) {}
}
