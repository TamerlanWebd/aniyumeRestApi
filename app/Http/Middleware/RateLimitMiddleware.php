<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $limiter = 'global'): Response
    {
        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, $this->getMaxAttempts($limiter))) {
            // Блокируем IP на 1 час при превышении лимита
            $this->blockIp($request->ip());
            
            return response()->json([
                'message' => 'Too many attempts. Your IP has been temporarily blocked.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }

        RateLimiter::hit($key, $this->getDecaySeconds($limiter));

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $this->getMaxAttempts($limiter),
            'X-RateLimit-Remaining' => RateLimiter::remaining($key, $this->getMaxAttempts($limiter)),
        ]);
    }

    /**
     * Resolve request signature.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        return sha1(
            $request->method() .
            '|' . $request->server('SERVER_NAME') .
            '|' . $request->path() .
            '|' . $request->ip()
        );
    }

    /**
     * Get max attempts for limiter.
     */
    protected function getMaxAttempts(string $limiter): int
    {
        return match($limiter) {
            'auth' => 5,      // 5 попыток авторизации
            'api' => 60,      // 60 API запросов
            'global' => 100,  // 100 общих запросов
            default => 60
        };
    }

    /**
     * Get decay time in seconds.
     */
    protected function getDecaySeconds(string $limiter): int
    {
        return match($limiter) {
            'auth' => 300,    // 5 минут
            'api' => 60,      // 1 минута
            'global' => 60,   // 1 минута
            default => 60
        };
    }

    /**
     * Block IP address.
     */
    protected function blockIp(string $ip): void
    {
        Cache::put('blocked_ip:' . $ip, true, now()->addHour());
    }
}
