<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    /**
     * Register a new webhook
     */
    public function register(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'events' => 'required|array',
            'events.*' => 'in:anime.created,anime.updated,anime.deleted',
        ]);
        
        $webhookId = uniqid('webhook_');
        $webhook = [
            'id' => $webhookId,
            'url' => $request->url,
            'events' => $request->events,
            'created_at' => now()->toIso8601String(),
        ];
        
        // Save to cache (simulating DB)
        foreach ($request->events as $event) {
            $key = "webhooks:" . str_replace('.', '_', $event);
            $webhooks = Cache::get($key, []);
            $webhooks[$webhookId] = $webhook;
            Cache::put($key, $webhooks, 86400 * 30);
        }
        
        return response()->json([
            'message' => 'Webhook registered successfully',
            'webhook' => $webhook,
        ], 201);
    }
    
    /**
     * List registered webhooks
     */
    public function list()
    {
        $events = ['anime_created', 'anime_updated', 'anime_deleted'];
        $allWebhooks = [];
        
        foreach ($events as $event) {
            $webhooks = Cache::get("webhooks:{$event}", []);
            $allWebhooks = array_merge($allWebhooks, array_values($webhooks));
        }
        
        // Unique by ID
        $unique = collect($allWebhooks)->unique('id')->values();
        
        return response()->json(['webhooks' => $unique]);
    }
    
    /**
     * Delete a webhook
     */
    public function delete($id)
    {
        $events = ['anime_created', 'anime_updated', 'anime_deleted'];
        $found = false;
        
        foreach ($events as $event) {
            $key = "webhooks:{$event}";
            $webhooks = Cache::get($key, []);
            
            if (isset($webhooks[$id])) {
                unset($webhooks[$id]);
                Cache::put($key, $webhooks, 86400 * 30);
                $found = true;
            }
        }
        
        return $found
            ? response()->json(['message' => 'Webhook deleted'])
            : response()->json(['error' => 'Webhook not found'], 404);
    }
}
