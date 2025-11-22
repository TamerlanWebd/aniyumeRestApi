<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AnimeController;

Route::middleware('throttle.api')->group(function () {
    Route::get('anime', [AnimeController::class, 'index']);
    Route::get('anime/{id}', [AnimeController::class, 'show']);
});

Route::middleware('throttle.auth')->group(function () {
    Route::post('/auth/google', [AuthController::class, 'googleAuth']);
});

Route::middleware(['auth:sanctum', 'throttle.api'])->group(function () {
    
    Route::get('/admin-check', [AuthController::class, 'checkAdmin']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::middleware('admin')->group(function () {
        Route::post('anime', [AnimeController::class, 'store']);
        Route::put('anime/{id}', [AnimeController::class, 'update']);
        Route::patch('anime/{id}', [AnimeController::class, 'update']);
        Route::delete('anime/{id}', [AnimeController::class, 'destroy']);
    });
});

use App\Http\Controllers\HealthCheckController;

Route::get('/health', [HealthCheckController::class, 'check']);