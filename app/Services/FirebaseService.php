<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected $database;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')))
            ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));

        $this->database = $factory->createDatabase();
    }

    public function getAnime($anilistId)
    {
        try {
            $reference = $this->database->getReference('animes/' . $anilistId);
            $snapshot = $reference->getSnapshot();

            if ($snapshot->exists()) {
                return $snapshot->getValue();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Firebase getAnime Error: ' . $e->getMessage());
            return null;
        }
    }

    public function saveAnime($anilistId, $data)
    {
        try {
            $this->database->getReference('animes/' . $anilistId)->set($data);
            return true;
        } catch (\Exception $e) {
            Log::error('Firebase saveAnime Error: ' . $e->getMessage());
            return false;
        }
    }

    public function incrementViews($anilistId)
    {
        try {
            $reference = $this->database->getReference('animes/' . $anilistId . '/views_count');
            
            $this->database->runTransaction(function (Database\Transaction $transaction) use ($reference) {
                $snapshot = $transaction->snapshot($reference);
                $currentValue = $snapshot->getValue() ?: 0;
                $transaction->set($reference, $currentValue + 1);
            });
            
            return true;
        } catch (\Exception $e) {
            Log::error('Firebase incrementViews Error: ' . $e->getMessage());
            return false;
        }
    }

    public function needsSync($animeData)
    {
        if (!isset($animeData['last_synced_at'])) {
            return true;
        }

        $lastSynced = \Carbon\Carbon::parse($animeData['last_synced_at']);
        return $lastSynced->diffInHours(now()) >= 24;
    }
}
