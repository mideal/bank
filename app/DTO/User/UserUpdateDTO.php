<?php

namespace App\DTO\User;

class UserUpdateDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?int $age,
        public readonly array $fill,
    ) {}
}
