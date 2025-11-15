<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateRequestMiddleware
{
    /**
     * Максимальный размер запроса в байтах (10MB)
     */
    protected int $maxRequestSize = 10485760;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Проверка размера запроса
        if ($request->header('Content-Length') > $this->maxRequestSize) {
            return response()->json([
                'message' => 'Request entity too large.'
            ], 413);
        }

        // Проверка подозрительных символов в параметрах
        if ($this->hasSuspiciousInput($request)) {
            return response()->json([
                'message' => 'Invalid request data detected.'
            ], 400);
        }

        // Проверка SQL injection попыток
        if ($this->hasSqlInjectionAttempt($request)) {
            return response()->json([
                'message' => 'Malicious request detected.'
            ], 400);
        }

        // Проверка XSS попыток
        if ($this->hasXssAttempt($request)) {
            return response()->json([
                'message' => 'Malicious script detected.'
            ], 400);
        }

        return $next($request);
    }

    /**
     * Check for suspicious input patterns.
     */
    protected function hasSuspiciousInput(Request $request): bool
    {
        $input = json_encode($request->all());
        
        $patterns = [
            '/<script[\s\S]*?>[\s\S]*?<\/script>/i',
            '/javascript:/i',
            '/onerror=/i',
            '/onload=/i',
            '/eval\(/i',
            '/base64_decode/i',
            '/exec\(/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for SQL injection attempts.
     */
    protected function hasSqlInjectionAttempt(Request $request): bool
    {
        $input = strtolower(json_encode($request->all()));
        
        $sqlPatterns = [
            'union select',
            'drop table',
            'insert into',
            'delete from',
            'update set',
            '--',
            '/*',
            'xp_cmdshell',
            'exec sp_',
        ];

        foreach ($sqlPatterns as $pattern) {
            if (str_contains($input, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for XSS attempts.
     */
    protected function hasXssAttempt(Request $request): bool
    {
        $input = json_encode($request->all());
        
        $xssPatterns = [
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/onclick=/i',
        ];

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }
}
