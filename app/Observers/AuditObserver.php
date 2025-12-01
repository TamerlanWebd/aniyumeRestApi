<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditObserver
{
    public function created(Model $model)
    {
        $this->log($model, 'create', [], $model->toArray());
        // TODO: Broadcast update
    }

    public function updated(Model $model)
    {
        $this->log($model, 'update', $model->getOriginal(), $model->getChanges());
        // TODO: Broadcast update
    }

    public function deleted(Model $model)
    {
        $this->log($model, 'delete', $model->toArray(), []);
        // TODO: Broadcast update
    }

    protected function log(Model $model, string $action, array $oldValues = [], array $newValues = [])
    {
        // Skip logging for AuditLog itself to prevent infinite loops
        if ($model instanceof AuditLog) {
            return;
        }

        AuditLog::create([
            'user_id' => Auth::id() ?? null, // System action if null
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);

        // Broadcast stats update
        try {
            $stats = [
                'anime_count' => \App\Models\Anime::count(),
                'user_count' => \App\Models\User::count(),
                'recent_action' => [
                    'action' => $action,
                    'model' => class_basename($model),
                    'time' => now()->toIso8601String(),
                ]
            ];
            event(new \App\Events\DashboardStatsUpdated($stats));
        } catch (\Exception $e) {
            // Fail silently if broadcasting fails (e.g. connection issues)
        }
    }
}
