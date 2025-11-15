<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        // глобальные middleware
    ];

    protected $middlewareGroups = [
        'web' => [
            // web middleware
        ],

        'api' => [
        \App\Http\Middleware\AuthenticateFromCookie::class,
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
    ];

    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'admin' => \App\Http\Middleware\IsAdmin::class, // твой кастомный
        // и другие middleware
    ];
}
