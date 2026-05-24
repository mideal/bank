<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\Undefined;
use App\Dto\User\UserUpdateDto;
use App\Exceptions\EmailAlreadyTakenException;
use App\Exceptions\UserNotFoundException;
use App\Models\User;
use App\Repositories\UserRepository;

final readonly class UserService
{
    public function __construct(private UserRepository $userRepository) {}

    public function update(int $userId, UserUpdateDto $userUpdateDto): User
    {
        $user = $this->userRepository->find($userId);

        if (! $user instanceof User) {
            throw new UserNotFoundException($userId);
        }

        if ($userUpdateDto->fill() === []) {
            return $user;
        }

        if (! $userUpdateDto->email instanceof Undefined &&
            $userUpdateDto->email !== $user->email
        ) {
            $existingUser = $this->userRepository->findByEmail($userUpdateDto->email);
            if ($existingUser && $existingUser->id !== $userId) {
                throw new EmailAlreadyTakenException($userUpdateDto->email);
            }
        }

        $user->fill($userUpdateDto->fill());

        return $this->userRepository->save($user);
    }
}
