<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Google\Cloud\Firestore\FirestoreClient;

class FirestoreServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(FirestoreClient::class, function ($app) {
            $credentialsPath = storage_path('app/firebase/firebase-credentials.json');
            
            if (!file_exists($credentialsPath)) {
                throw new \RuntimeException("Firebase credentials not found at: {$credentialsPath}");
            }

            $credentials = json_decode(file_get_contents($credentialsPath), true);

            return new FirestoreClient([
                'projectId' => $credentials['project_id'],
                'keyFilePath' => $credentialsPath,
                'transport' => 'rest',
            ]);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
