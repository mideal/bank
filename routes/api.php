<?php

declare(strict_types=1);

use App\Http\Controllers\AccountController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('user', [UserController::class, 'update']);
    Route::post('accounts/{account}/deposit', [AccountController::class, 'deposit']);
    Route::post('accounts/{account}/transfer', [AccountController::class, 'transfer']);
});
