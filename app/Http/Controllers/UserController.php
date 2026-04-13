<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\User\UserResponse;
use App\Services\UserService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function update(Request $request, UpdateUserRequest $updateUserRequest, ApiResponse $apiResponse): JsonResponse
    {

        $userId = $request->user()?->id;

        if ($userId === null) {
            throw new AuthenticationException;
        }

        $user = $this->userService->update(
            $userId,
            $updateUserRequest->toUserUpdateDTO(),
        );

        $apiResponse->data = new UserResponse(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            age: $user->age,
        );

        return new JsonResponse($apiResponse);
    }
}
