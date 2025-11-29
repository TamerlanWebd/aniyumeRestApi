<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AnimeController; // V1 by default or alias
use App\Http\Controllers\Api\AnimeSearchController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\ApiAnalyticsMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Apply Analytics Middleware Globally to API
Route::middleware([ApiAnalyticsMiddleware::class])->group(function () {

    // ==================== PUBLIC ENDPOINTS ====================
    Route::middleware('throttle:guest')->group(function () {
        // Health Check
        Route::get('/health', [HealthCheckController::class, 'check']);
        
        // API V1 (Legacy)
        Route::prefix('v1')->group(function () {
            Route::get('anime', [\App\Http\Controllers\Api\V1\AnimeController::class, 'index']);
            Route::get('anime/{id}', [\App\Http\Controllers\Api\V1\AnimeController::class, 'show']);
        });
        
        // API V2 (Advanced with Cache)
        Route::prefix('v2')->group(function () {
            Route::get('anime', [\App\Http\Controllers\Api\V2\AnimeController::class, 'index']);
            Route::get('anime/{id}', [\App\Http\Controllers\Api\V2\AnimeController::class, 'show']);
        });
        
        // Advanced Search & Filtering
        Route::get('anime/search', [AnimeSearchController::class, 'search']);
        Route::get('anime/autocomplete', [AnimeSearchController::class, 'autocomplete']);
        Route::get('anime/genres', [AnimeSearchController::class, 'genres']);

        // Default route (pointing to V2 for convenience or V1 if preferred)
        Route::get('anime', [\App\Http\Controllers\Api\V2\AnimeController::class, 'index']);
        Route::get('anime/{id}', [\App\Http\Controllers\Api\V2\AnimeController::class, 'show']);
    });

    // ==================== AUTHENTICATION ====================
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/auth/google', [AuthController::class, 'googleAuth']);
    });

    // ==================== PROTECTED ENDPOINTS ====================
    Route::middleware(['auth:sanctum', 'throttle:user'])->group(function () {
        Route::get('/admin-check', [AuthController::class, 'checkAdmin']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        
        // ==================== ADMIN ENDPOINTS ====================
        Route::middleware(['throttle:admin'])->group(function () {
            
            // Moderator+ can update
            Route::middleware('role:moderator')->group(function() {
                Route::put('anime/{id}', [\App\Http\Controllers\Api\AnimeController::class, 'update']);
                Route::patch('anime/{id}', [\App\Http\Controllers\Api\AnimeController::class, 'update']);
            });

            // Admin only can delete and create
            Route::middleware('role:admin')->group(function() {
                Route::post('anime', [\App\Http\Controllers\Api\AnimeController::class, 'store']);
                Route::delete('anime/{id}', [\App\Http\Controllers\Api\AnimeController::class, 'destroy']);
                
                // Analytics & Webhooks (Admin only)
                Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
                Route::get('/dashboard/stats', [AnalyticsController::class, 'realTimeStats']);
                Route::get('/audit-logs', [AnalyticsController::class, 'auditLogs']);
                Route::delete('/analytics/clear', [AnalyticsController::class, 'clearAnalytics']);
                
                Route::post('/webhooks', [WebhookController::class, 'register']);
                Route::get('/webhooks', [WebhookController::class, 'list']);
                Route::delete('/webhooks/{id}', [WebhookController::class, 'delete']);
            });
        });
    });

});