<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $requestId = uniqid('req_');

        // Log Request
        Log::info("[$requestId] 📥 Incoming Request", [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'body' => $request->except(['password', 'password_confirmation']),
        ]);

        try {
            $response = $next($request);
        } catch (\Exception $e) {
            Log::error("[$requestId] ❌ Exception", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        // Log Response
        Log::info("[$requestId] 📤 Response Sent", [
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
        ]);

        return $response;
    }
}
