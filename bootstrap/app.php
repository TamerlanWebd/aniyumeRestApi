<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AuthenticateFromCookie;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\BlockedIpMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\ValidateRequestMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
         $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        $middleware->append(BlockedIpMiddleware::class);
        $middleware->append(SecurityHeadersMiddleware::class);
        $middleware->append(ValidateRequestMiddleware::class);
        
        // API middleware группа
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\LogRequestMiddleware::class,
        ]);

        // ОТКЛЮЧАЕМ CSRF для API (Sanctum использует tokens)
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'sanctum/csrf-cookie',
        ]);

        // Middleware алиасы
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'auth.cookie' => AuthenticateFromCookie::class,
            'throttle.auth' => RateLimitMiddleware::class . ':auth',
            'throttle.api' => RateLimitMiddleware::class . ':api',
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }
        });
    })->create();
