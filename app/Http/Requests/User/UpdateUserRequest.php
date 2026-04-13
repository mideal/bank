<?php

namespace App\Http\Requests\User;

use App\DTO\User\UserUpdateDTO;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class UpdateUserRequest extends Data
{
    public function __construct(
        #[Sometimes, StringType, Max(255)]
        public readonly string|Optional $name,

        #[Sometimes, Email]
        public readonly string|Optional $email,

        #[Sometimes, Nullable, IntegerType, Min(1), Max(150)]
        public readonly int|null|Optional $age,
    ) {}

    public function toUserUpdateDTO(): UserUpdateDTO
    {
        return new UserUpdateDTO(
            name: $this->name instanceof Optional ? null : $this->name,
            email: $this->email instanceof Optional ? null : $this->email,
            age: $this->age instanceof Optional ? null : $this->age,
            fill: array_keys($this->all())
        );
    }
}
