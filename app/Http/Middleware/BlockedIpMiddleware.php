<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class BlockedIpMiddleware
{
    /**
     * Черный список IP адресов (можно добавлять вручную)
     */
    protected array $blacklist = [
        // Добавляй сюда подозрительные IP
        // '192.168.1.100',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // Проверяем черный список
        if (in_array($ip, $this->blacklist)) {
            return response()->json([
                'message' => 'Access denied.'
            ], 403);
        }

        // Проверяем временную блокировку
        if (Cache::has('blocked_ip:' . $ip)) {
            return response()->json([
                'message' => 'Your IP is temporarily blocked due to suspicious activity.',
                'retry_after' => Cache::get('blocked_ip:' . $ip)
            ], 403);
        }

        // Проверяем подозрительные User-Agent
        if ($this->isSuspiciousUserAgent($request)) {
            Cache::put('blocked_ip:' . $ip, true, now()->addHours(24));
            return response()->json([
                'message' => 'Suspicious activity detected.'
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if User-Agent is suspicious.
     */
    protected function isSuspiciousUserAgent(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        
        $suspiciousPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 
            'curl', 'wget', 'python', 'java',
            'nikto', 'sqlmap', 'nmap', 'masscan'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
