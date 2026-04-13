<?php

declare(strict_types=1);

namespace App\Http\Responses\User;

class UserResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?int $age,
    ) {}
}
