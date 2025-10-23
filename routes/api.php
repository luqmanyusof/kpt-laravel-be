<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('users/insert-fake', [UserController::class, 'insertFake']);
Route::get('users', [UserController::class, 'index']);
Route::post('users', [UserController::class, 'store']);
Route::get('users/{id}', [UserController::class, 'show']);
Route::put('users/{id}', [UserController::class, 'update']);
Route::patch('users/{id}', [UserController::class, 'update']);
Route::delete('users/{id}', [UserController::class, 'destroy']);

Route::post('auth/login', [UserController::class, 'login'])->middleware('throttle:10,1');

// Protected endpoints (require Authorization: Bearer <token>)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [UserController::class, 'me']);
    Route::post('auth/logout', [UserController::class, 'logout']);
    Route::get('users/{id}/secure', [UserController::class, 'showProtected']);
    Route::put('users/{id}/secure', [UserController::class, 'updateProtected']);
});
