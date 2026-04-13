<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    public function save(User $user): User
    {
        $user->save();

        return $user;
    }
}
