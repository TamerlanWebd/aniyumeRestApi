<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAnimeSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $animeData;

    public function __construct($animeData)
    {
        $this->animeData = $animeData;
    }

    public function handle(): void
    {
        // Simulate heavy processing (e.g., image resizing, AI analysis)
        Log::info("Starting processing for anime: " . ($this->animeData['title'] ?? 'Unknown'));
        
        sleep(2); // Simulate delay
        
        Log::info("Finished processing for anime: " . ($this->animeData['title'] ?? 'Unknown'));
    }
}
