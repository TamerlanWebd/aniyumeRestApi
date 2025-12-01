<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserAnimeController extends Controller
{
    public function __construct(
        private FirebaseService $firebaseService
    ) {}

    // Favorites
  public function getFavorites(Request $request): JsonResponse
{
    \Log::info('getFavorites: start');

    $user = $request->user();
    \Log::info('getFavorites: user resolved', ['id' => $user?->id, 'class' => $user ? get_class($user) : null]);

    $userId = (string) $user->id;
    \Log::info('getFavorites: userId', ['userId' => $userId]);

    $favorites = $this->firebaseService->getFavorites($userId);
    \Log::info('getFavorites: favorites fetched', ['count' => count($favorites)]);

    return response()->json($favorites);
}


    public function addToFavorites(Request $request, int $animeId): JsonResponse
    {
        $user = $request->user();
        $userId = (string) $user->id;

        $this->firebaseService->addToFavorites($userId, $animeId);

        return response()->json(['message' => 'Added to favorites']);
    }

    public function removeFromFavorites(Request $request, int $animeId): JsonResponse
    {
        $user = $request->user();
        $userId = (string) $user->id;

        $this->firebaseService->removeFromFavorites($userId, $animeId);

        return response()->json(['message' => 'Removed from favorites']);
    }

    // Lists
    public function getList(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = (string) $user->id;
        $status = $request->query('status');

        $list = $this->firebaseService->getUserList($userId, $status);

        return response()->json($list);
    }

    public function addToList(Request $request, int $animeId): JsonResponse
    {
        $validated = $request->validate([
            'status'   => 'required|in:watching,completed,plan_to_watch,dropped,on_hold',
            'progress' => 'nullable|integer|min:0',
            'rating'   => 'nullable|numeric|min:0|max:10',
            'notes'    => 'nullable|string',
        ]);

        $user = $request->user();
        $userId = (string) $user->id;

        $this->firebaseService->addToList($userId, $animeId, $validated);

        return response()->json(['message' => 'Added to list']);
    }

    public function updateListItem(Request $request, string $itemId): JsonResponse
    {
        $validated = $request->validate([
            'status'   => 'sometimes|in:watching,completed,plan_to_watch,dropped,on_hold',
            'progress' => 'sometimes|integer|min:0',
            'rating'   => 'sometimes|numeric|min:0|max:10',
            'notes'    => 'sometimes|string',
        ]);

        $user = $request->user();
        $userId = (string) $user->id;

        $this->firebaseService->updateListItem($userId, $itemId, $validated);

        return response()->json(['message' => 'List item updated']);
    }

    public function removeFromList(Request $request, string $itemId): JsonResponse
    {
        $user = $request->user();
        $userId = (string) $user->id;

        $this->firebaseService->removeFromList($userId, $itemId);

        return response()->json(['message' => 'Removed from list']);
    }

    // History
    public function getHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = (string) $user->id;
        $limit = (int) $request->query('limit', 50);

        $history = $this->firebaseService->getHistory($userId, $limit);

        return response()->json($history);
    }

    public function addToHistory(Request $request, int $animeId): JsonResponse
    {
        $validated = $request->validate([
            'episode'  => 'required|integer|min:1',
            'duration' => 'nullable|integer|min:0',
            'progress' => 'nullable|integer|min:0|max:100',
        ]);

        $user = $request->user();
        $userId = (string) $user->id;

        $this->firebaseService->addToHistory(
            $userId,
            $animeId,
            $validated['episode'],
            $validated
        );

        return response()->json(['message' => 'Added to history']);
    }
}
