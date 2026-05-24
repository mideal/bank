<?php

namespace App\Http\Requests\User;

use App\Dto\Undefined;
use App\Dto\User\UserUpdateDto;
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

    public function toUserUpdateDto(): UserUpdateDto
    {
        return new UserUpdateDto(
            name: $this->name instanceof Optional ? new Undefined : $this->name,
            email: $this->email instanceof Optional ? new Undefined : $this->email,
            age: $this->age instanceof Optional ? new Undefined : $this->age
        );
    }
}
