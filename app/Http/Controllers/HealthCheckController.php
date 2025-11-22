<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\FirestoreRestService;

class HealthCheckController extends Controller
{
    public function check()
    {
        $status = 'ok';
        $checks = [];
        
        // Database check (if configured)
        try {
            // Only check if DB_HOST is set, otherwise skip
            if (env('DB_HOST') || env('DB_CONNECTION') === 'sqlite') {
                DB::connection()->getPdo();
                $checks['database'] = 'ok';
            } else {
                $checks['database'] = 'skipped';
            }
        } catch (\Exception $e) {
            $checks['database'] = 'error: ' . $e->getMessage();
            $status = 'degraded';
        }
        
        // Cache check
        try {
            Cache::put('health_check', true, 10);
            $checks['cache'] = Cache::get('health_check') ? 'ok' : 'error';
        } catch (\Exception $e) {
            $checks['cache'] = 'error';
            $status = 'degraded';
        }
        
        // Firestore check
        try {
            $firestore = app(FirestoreRestService::class);
            // Just list 1 document to verify connection
            $firestore->collection('anime')->list(1, 0);
            $checks['firestore'] = 'ok';
        } catch (\Exception $e) {
            $checks['firestore'] = 'error: ' . $e->getMessage();
            $status = 'degraded';
        }
        
        return response()->json([
            'status' => $status,
            'server_time' => now()->toIso8601String(),
            'uptime' => $this->getUptime(),
            'memory_usage' => [
                'used' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            ],
            'checks' => $checks,
        ]);
    }
    
    private function getUptime()
    {
        // Windows specific uptime check or fallback
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
             // Try to get system uptime via wmic
             $output = shell_exec('wmic os get lastbootuptime');
             if ($output) {
                 return trim(str_replace('LastBootUpTime', '', $output));
             }
        } elseif (function_exists('shell_exec')) {
            $uptime = shell_exec('uptime');
            return trim($uptime);
        }
        return 'N/A';
    }
}
