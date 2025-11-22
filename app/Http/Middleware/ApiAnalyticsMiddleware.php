<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiAnalyticsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $requestId = uniqid('req_');
        
        // Save request ID for logs
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        // Record analytics
        $this->recordAnalytics($request, $response, $duration);
        
        // Add debug headers
        $response->headers->set('X-Request-ID', $requestId);
        $response->headers->set('X-Response-Time', $duration . 'ms');
        
        return $response;
    }
    
    private function recordAnalytics(Request $request, Response $response, float $duration)
    {
        $endpoint = $request->path();
        $method = $request->method();
        $status = $response->getStatusCode();
        $date = now()->format('Y-m-d');
        
        // Increment counters
        Cache::increment("analytics:total_requests");
        Cache::increment("analytics:requests:{$date}");
        Cache::increment("analytics:method:{$method}");
        Cache::increment("analytics:endpoint:{$method}:{$endpoint}");
        Cache::increment("analytics:status:{$status}");
        
        // Average duration
        $avgKey = "analytics:avg_duration";
        $currentAvg = Cache::get($avgKey, 0);
        $totalRequests = Cache::get("analytics:total_requests", 1);
        // Avoid division by zero if totalRequests is somehow 0 (though incremented above)
        $totalRequests = $totalRequests > 0 ? $totalRequests : 1;
        
        $newAvg = (($currentAvg * ($totalRequests - 1)) + $duration) / $totalRequests;
        Cache::put($avgKey, round($newAvg, 2), 86400);
        
        // Recent requests
        $recentKey = "analytics:recent_requests";
        $recent = Cache::get($recentKey, []);
        array_unshift($recent, [
            'timestamp' => now()->toIso8601String(),
            'method' => $method,
            'endpoint' => $endpoint,
            'status' => $status,
            'duration' => $duration,
            'ip' => $request->ip(),
        ]);
        $recent = array_slice($recent, 0, 100);
        Cache::put($recentKey, $recent, 86400);
    }
}
