<?php

namespace App\Listeners;

use App\Events\AnimeCreated;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TriggerWebhooks
{
    public function handle(AnimeCreated $event)
    {
        // Get webhooks from cache (simulated storage)
        $webhooks = Cache::get('webhooks:anime_created', []);
        
        foreach ($webhooks as $webhook) {
            try {
                Http::timeout(5)->post($webhook['url'], [
                    'event' => 'anime.created',
                    'timestamp' => now()->toIso8601String(),
                    'data' => $event->anime,
                ]);
                
                Log::info("Webhook triggered: {$webhook['url']}");
            } catch (\Exception $e) {
                Log::error("Webhook failed: {$webhook['url']} - " . $e->getMessage());
            }
        }
    }
}
