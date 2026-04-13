<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::patch('user', [UserController::class, 'update'])->middleware('auth:sanctum');
