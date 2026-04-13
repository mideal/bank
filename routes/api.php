<?php

declare(strict_types=1);

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::patch('user', [UserController::class, 'update'])->middleware('auth:sanctum');
