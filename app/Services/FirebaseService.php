<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Contract\Firestore;
use Google\Cloud\Firestore\FirestoreClient;

class FirebaseService
{
    private ?FirestoreClient $firestore = null;

private function getFirestore(): FirestoreClient
{
    if ($this->firestore === null) {
        $path = 'C:/Users/Тамерлан/Desktop/CURS/aniyume-api/storage/app/firebase/firebase-credentials.json';

        \Log::info('FirebaseService:getFirestore: start', ['path' => $path, 'exists' => file_exists($path)]);

        if (!file_exists($path)) {
            \Log::error('FirebaseService:getFirestore: credentials file NOT FOUND', ['path' => $path]);
            throw new \RuntimeException("Firebase credentials file NOT FOUND at: {$path}");
        }

        $factory = (new Factory)->withServiceAccount($path);
        \Log::info('FirebaseService:getFirestore: factory created');

        $this->firestore = $factory->createFirestore()->database();
        \Log::info('FirebaseService:getFirestore: firestore client created');
    }

    return $this->firestore;
}


    // User Profile
    public function createUserProfile(string $userId, array $data): void
    {
        $this->getFirestore()->collection('users')->document($userId)->set(array_merge($data, [
            'createdAt' => new \DateTime(),
            'updatedAt' => new \DateTime(),
        ]));
    }

    public function getUserProfile(string $userId): ?array
    {
        $snapshot = $this->getFirestore()->collection('users')->document($userId)->snapshot();
        return $snapshot->exists() ? $snapshot->data() : null;
    }

    public function updateUserProfile(string $userId, array $data): void
    {
        $this->getFirestore()->collection('users')->document($userId)->update(array_merge($data, [
            ['path' => 'updatedAt', 'value' => new \DateTime()],
        ]));
    }

    // Favorites
    public function addToFavorites(string $userId, int $animeId): void
    {
        $this->getFirestore()
            ->collection('users')
            ->document($userId)
            ->collection('favorites')
            ->document((string) $animeId)
            ->set([
                'anilistId' => $animeId,
                'addedAt'   => new \DateTime(),
            ]);
    }

    public function removeFromFavorites(string $userId, int $animeId): void
    {
        $this->getFirestore()
            ->collection('users')
            ->document($userId)
            ->collection('favorites')
            ->document((string) $animeId)
            ->delete();
    }

    public function getFavorites(string $userId): array
    {
        $favorites = $this->getFirestore()
            ->collection('users')
            ->document($userId)
            ->collection('favorites')
            ->documents();

        $result = [];
        foreach ($favorites as $favorite) {
            if ($favorite->exists()) {
                $result[] = $favorite->data();
            }
        }

        return $result;
    }

    // User Lists (watching, completed, etc.)
    public function addToList(string $userId, int $animeId, array $data): void
    {
        $itemId = uniqid('list_');

        $this->getFirestore()
            ->collection('users')
            ->document($userId)
            ->collection('lists')
            ->document($itemId)
            ->set(array_merge($data, [
                'animeId'   => $animeId,
                'updatedAt' => new \DateTime(),
            ]));
    }

    public function updateListItem(string $userId, string $itemId, array $data): void
    {
        $this->getFirestore()
            ->collection('users')
            ->document($userId)
            ->collection('lists')
            ->document($itemId)
            ->update(array_merge(
                [
                    ['path' => 'updatedAt', 'value' => new \DateTime()],
                ],
                array_map(
                    fn ($key, $value) => ['path' => $key, 'value' => $value],
                    array_keys($data),
                    $data
                )
            ));
    }

    public function removeFromList(string $userId, string $itemId): void
    {
        $this->getFirestore()
            ->collection('users')
            ->document($userId)
            ->collection('lists')
            ->document($itemId)
            ->delete();
    }

    public function getUserList(string $userId, ?string $status = null): array
    {
        $query = $this->getFirestore()
            ->collection('users')
            ->document($userId)
            ->collection('lists');

        if ($status) {
            $query = $query->where('status', '=', $status);
        }

        $documents = $query->documents();

        $result = [];
        foreach ($documents as $document) {
            if ($document->exists()) {
                $result[$document->id()] = $document->data();
            }
        }

        return $result;
    }

    // Watch History
    public function addToHistory(string $userId, int $animeId, int $episode, array $data): void
    {
        $episodeId = "{$animeId}_ep{$episode}";

        $this->getFirestore()
            ->collection('users')
            ->document($userId)
            ->collection('history')
            ->document($episodeId)
            ->set(array_merge($data, [
                'animeId'   => $animeId,
                'episode'   => $episode,
                'watchedAt' => new \DateTime(),
            ]));
    }

    public function getHistory(string $userId, int $limit = 50): array
    {
        $documents = $this->getFirestore()
            ->collection('users')
            ->document($userId)
            ->collection('history')
            ->orderBy('watchedAt', 'DESC')
            ->limit($limit)
            ->documents();

        $result = [];
        foreach ($documents as $document) {
            if ($document->exists()) {
                $result[] = $document->data();
            }
        }

        return $result;
    }

    // Comments
    public function addComment(int $animeId, string $userId, string $text, ?int $rating = null): string
    {
        $commentId = uniqid('comment_');

        $this->getFirestore()
            ->collection('animeComments')
            ->document((string) $animeId)
            ->collection('comments')
            ->document($commentId)
            ->set([
                'userId'    => $userId,
                'text'      => $text,
                'rating'    => $rating,
                'likes'     => 0,
                'createdAt' => new \DateTime(),
                'updatedAt' => new \DateTime(),
            ]);

        return $commentId;
    }

    public function getComments(int $animeId, int $limit = 20): array
    {
        $documents = $this->getFirestore()
            ->collection('animeComments')
            ->document((string) $animeId)
            ->collection('comments')
            ->orderBy('createdAt', 'DESC')
            ->limit($limit)
            ->documents();

        $result = [];
        foreach ($documents as $document) {
            if ($document->exists()) {
                $result[$document->id()] = $document->data();
            }
        }

        return $result;
    }

    // User Settings
    public function getUserSettings(string $userId): ?array
    {
        $snapshot = $this->getFirestore()
            ->collection('userSettings')
            ->document($userId)
            ->snapshot();

        return $snapshot->exists() ? $snapshot->data() : null;
    }

    public function updateUserSettings(string $userId, array $settings): void
    {
        $this->getFirestore()
            ->collection('userSettings')
            ->document($userId)
            ->set($settings, ['merge' => true]);
    }
}
