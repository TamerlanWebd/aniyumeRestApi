<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AnimeController;

/*
|--------------------------------------------------------------------------
| API Routes - МАКСИМАЛЬНАЯ ЗАЩИТА
|--------------------------------------------------------------------------
| Rate Limiting:
| - throttle.auth: 5 попыток / 5 минут (авторизация)
| - throttle.api: 60 запросов / 1 минута (API)
| - auth.cookie: проверка httpOnly cookie
|--------------------------------------------------------------------------
*/

// ============================================
// ПУБЛИЧНЫЕ МАРШРУТЫ (Только чтение аниме)
// ============================================
Route::middleware(['throttle.api'])->group(function () {
    // Получить список всех аниме
    Route::get('anime', [AnimeController::class, 'index']);
    
    // Получить одно аниме по ID
    Route::get('anime/{id}', [AnimeController::class, 'show']);
});

// ============================================
// АВТОРИЗАЦИЯ (Строгий rate limiting)
// ============================================
Route::middleware(['throttle.auth'])->group(function () {
    // Google OAuth авторизация
    Route::post('/auth/google', [AuthController::class, 'googleAuth']);
});

// ============================================
// ЗАЩИЩЕННЫЕ МАРШРУТЫ (Требуется авторизация)
// ============================================
Route::middleware(['auth.cookie', 'throttle.api'])->group(function () {
    
    // --- Проверка админа ---
    Route::get('/admin-check', function (Request $request) {
        $user = $request->user();
        
        return response()->json([
            'is_admin' => $user && $user->isAdmin(),
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'is_admin' => $user->isAdmin()
            ] : null
        ]);
    });
    
    // --- Выход ---
    Route::post('auth/logout', [AuthController::class, 'logout']);
    
    // --- Получить текущего пользователя ---
    Route::get('/user', function (Request $request) {
        return response()->json([
            'user' => $request->user()
        ]);
    });
    
    // --- CRUD для аниме (Только для админов) ---
    // Создать новое аниме
    Route::post('anime', [AnimeController::class, 'store']);
    
    // Обновить аниме
    Route::put('anime/{id}', [AnimeController::class, 'update']);
    
    // Удалить аниме
    Route::delete('anime/{id}', [AnimeController::class, 'destroy']);
});

// ============================================
// СЛУЖЕБНЫЕ МАРШРУТЫ
// ============================================

// Health check (без rate limiting для мониторинга)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'security' => 'maximum',
        'server_time' => now()->toDateTimeString(),
        'version' => '1.0.0'
    ]);
});

// Проверка rate limiting (для тестирования)
Route::get('/rate-limit-test', function () {
    return response()->json([
        'message' => 'Rate limit test endpoint',
        'tip' => 'Try refreshing this page multiple times to test rate limiting'
    ]);
})->middleware(['throttle.api']);
