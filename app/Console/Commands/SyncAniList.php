<?php

namespace App\Console\Commands;

use App\Services\AniListSyncService;
use Illuminate\Console\Command;

class SyncAniList extends Command
{
    protected $signature = 'anilist:sync {--page=1}';
    protected $description = 'Sync anime data from AniList API';

    public function handle(AniListSyncService $service): int
    {
        $page = (int) $this->option('page');

        $this->info("Starting AniList sync from page {$page}...");

        $service->syncAllAnime($page);

        $this->info('Sync completed!');

        return Command::SUCCESS;
    }
}
