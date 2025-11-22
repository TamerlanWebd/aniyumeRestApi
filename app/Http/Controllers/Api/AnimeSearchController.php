<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Services\FirestoreRestService;
use App\Http\Resources\AnimeResource;

class AnimeSearchController extends Controller
{
    protected $firestore;
    protected $collectionName = 'anime';

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    /**
     * Advanced Search
     * 
     * @OA\Get(
     *     path="/api/anime/search",
     *     summary="Search anime with filters",
     *     tags={"Anime"},
     *     @OA\Parameter(name="q", in="query", description="Search query"),
     *     @OA\Parameter(name="genre", in="query", description="Filter by genre"),
     *     @OA\Parameter(name="sort", in="query", description="Sort field (title, created_at)"),
     *     @OA\Parameter(name="order", in="query", description="Sort order (asc, desc)"),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $genre = $request->input('genre');
        $sortBy = $request->input('sort', 'title');
        $order = $request->input('order', 'asc');
        $limit = $request->input('limit', 20);
        
        // Fetch all anime for in-memory filtering (Prototype approach)
        // In production with Firestore, you would need composite indexes for this.
        $allAnime = $this->firestore->collection($this->collectionName)->list(100, 0);
        
        $filtered = collect($allAnime);
        
        // Search by title/description
        if ($query) {
            $filtered = $filtered->filter(function ($anime) use ($query) {
                return stripos($anime['title'] ?? '', $query) !== false ||
                       stripos($anime['description'] ?? '', $query) !== false;
            });
        }
        
        // Filter by genre
        if ($genre) {
            $filtered = $filtered->filter(function ($anime) use ($genre) {
                return stripos($anime['genre'] ?? '', $genre) !== false;
            });
        }
        
        // Sort
        $filtered = $order === 'desc' 
            ? $filtered->sortByDesc($sortBy) 
            : $filtered->sortBy($sortBy);
        
        // Pagination
        $results = $filtered->take($limit)->values();
        
        return response()->json([
            'data' => AnimeResource::collection($results),
            'meta' => [
                'total' => $filtered->count(),
                'returned' => $results->count(),
                'query' => [
                    'q' => $query,
                    'genre' => $genre,
                    'sort' => $sortBy,
                    'order' => $order,
                ],
            ],
        ]);
    }
    
    /**
     * Get popular genres
     */
    public function genres()
    {
        $allAnime = $this->firestore->collection($this->collectionName)->list(100, 0);
        
        $genres = collect($allAnime)
            ->pluck('genre')
            ->flatMap(fn($genre) => explode(',', $genre ?? ''))
            ->map(fn($g) => trim($g))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(10);
        
        return response()->json([
            'genres' => $genres->map(fn($count, $name) => [
                'name' => $name,
                'count' => $count,
            ])->values(),
        ]);
    }
    
    /**
     * Autocomplete suggestions
     */
    public function autocomplete(Request $request)
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }
        
        $allAnime = $this->firestore->collection($this->collectionName)->list(100, 0);
        
        $suggestions = collect($allAnime)
            ->filter(fn($anime) => stripos($anime['title'] ?? '', $query) !== false)
            ->take(10)
            ->map(fn($anime) => [
                'id' => $anime['id'],
                'title' => $anime['title'] ?? '',
                'genre' => $anime['genre'] ?? '',
            ])
            ->values();
        
        return response()->json(['suggestions' => $suggestions]);
    }
}
