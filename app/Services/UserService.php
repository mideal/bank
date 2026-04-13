<?php

namespace App\Services;

use App\DTO\User\UserUpdateDTO;
use App\Exceptions\UserNotFoundException;
use App\Models\User;
use App\Repositories\UserRepository;

final class UserService
{
    public function __construct(private readonly UserRepository $userRepository) {}

    public function update(int $userId, UserUpdateDTO $userUpdateDTO): User
    {
        $user = $this->userRepository->find($userId);

        if (! $user) {
            throw new UserNotFoundException($userId);
        }

        foreach ($userUpdateDTO->fill as $field) {
            $user->$field = $userUpdateDTO->$field;
        }

        return $this->userRepository->save($user);
    }
}
