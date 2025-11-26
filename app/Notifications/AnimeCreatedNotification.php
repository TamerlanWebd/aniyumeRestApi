<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnimeCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $anime;

    public function __construct($anime)
    {
        $this->anime = $anime;
    }

    public function via(object $notifiable): array
    {
        return ['mail']; // Add 'database' or 'broadcast' if needed
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('New Anime Added: ' . ($this->anime['title'] ?? 'Unknown'))
                    ->line('A new anime has been added to the catalog.')
                    ->line('Title: ' . ($this->anime['title'] ?? 'N/A'))
                    ->line('Genre: ' . ($this->anime['genre'] ?? 'N/A'))
                    ->action('View Anime', url('/api/v2/anime/' . ($this->anime['id'] ?? '')))
                    ->line('Thank you for using our application!');
    }
}
