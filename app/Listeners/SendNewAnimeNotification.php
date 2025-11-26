<?php

namespace App\Listeners;

use App\Events\AnimeCreated;
use App\Notifications\AnimeCreatedNotification;
use Illuminate\Support\Facades\Notification;
use App\Models\User; // Assuming User model exists

class SendNewAnimeNotification
{
    public function handle(AnimeCreated $event)
    {
        // In a real app, you might notify all users or subscribed users.
        // For demo, we'll notify the admin or a dummy user.
        
        // $users = User::all(); 
        // Notification::send($users, new AnimeCreatedNotification($event->anime));
        
        // Logging for demonstration since we might not have mail configured
        \Illuminate\Support\Facades\Log::info("Notification sent for anime: " . ($event->anime['title'] ?? 'Unknown'));
    }
}
