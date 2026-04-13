<?php

namespace App\Services;

use App\DTO\Undefined;
use App\DTO\User\UserUpdateDTO;
use App\Exceptions\EmailAlreadyTakenException;
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

        if ($userUpdateDTO->fill() === []) {
            return $user;
        }

        if (! $userUpdateDTO->email instanceof Undefined &&
            $userUpdateDTO->email !== $user->email
        ) {
            $existingUser = $this->userRepository->findByEmail($userUpdateDTO->email);
            if ($existingUser && $existingUser->id !== $userId) {
                throw new EmailAlreadyTakenException($userUpdateDTO->email);
            }
        }

        $user->fill($userUpdateDTO->fill());

        return $this->userRepository->save($user);
    }
}
