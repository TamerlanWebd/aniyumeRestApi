<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    protected $firestore;
    protected $collection = 'audit_logs';

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function log(string $action, string $resource, string $resourceId, array $oldValues = [], array $newValues = [])
    {
        $user = Auth::user();
        
        $logData = [
            'action' => $action, // CREATE, UPDATE, DELETE
            'resource' => $resource, // Anime, User
            'resource_id' => $resourceId,
            'user_id' => $user ? $user->id : 'system',
            'user_email' => $user ? $user->email : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'created_at' => now()->toIso8601String(),
        ];

        // Fire and forget (or queue in production)
        try {
            $this->firestore->collection($this->collection)->add($logData);
        } catch (\Exception $e) {
            // Fail silently for audit logs to not break main flow, or log to file
            \Illuminate\Support\Facades\Log::error("Audit Log Failed: " . $e->getMessage());
        }
    }
}
