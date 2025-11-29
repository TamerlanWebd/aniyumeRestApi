<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Models\Anime;
use App\Services\AniListService;
use App\Services\FirebaseService;
use App\Http\Resources\AnimeResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Anime V2",
<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Models\Anime;
use App\Services\AniListService;
use App\Services\FirebaseService; // Added FirebaseService import
use App\Http\Resources\AnimeResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Anime V2",
 *     description="Advanced API Endpoints with Caching"
 * )
 */
class AnimeController extends Controller
{
    protected $aniListService;
    protected $firebaseService;

    public function __construct(AniListService $aniListService, FirebaseService $firebaseService)
    {
        $this->aniListService = $aniListService;
        $this->firebaseService = $firebaseService;
    }

    /**
     * @OA\Get(
     *      path="/api/v2/anime",
     *      operationId="getAnimeListV2",
     *      tags={"Anime V2"},
     *      summary="Get list of anime (Cached)",
     *      description="Returns list of anime from local DB",
     *      @OA\Parameter(name="page", in="query", description="Page number", required=false, @OA\Schema(type="integer")),
     *      @OA\Parameter(name="limit", in="query", description="Items per page", required=false, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request)
    {
        // For now, index still uses local DB or could be implemented to fetch from Firebase list if needed.
        // Keeping local DB for list to avoid fetching all Firebase nodes.
        $limit = $request->input('limit', 10);
        $animeList = Anime::paginate($limit);
        return AnimeResource::collection($animeList);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/anime/{id}",
     *      operationId="getAnimeByIdV2",
     *      tags={"Anime V2"},
     *      summary="Get anime by ID (Cached)",
     *      description="Returns anime detail. Fetches from AniList if missing or stale in Firebase.",
     *      @OA\Parameter(name="id", in="path", description="Anime ID (AniList ID)", required=true, @OA\Schema(type="string")),
     *      @OA\Response(response=200, description="Success"),
     *      @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show($id)
    {
        try {
            // 1. Check Firebase
            $animeData = $this->firebaseService->getAnime($id);
            $cached = true;

            // 2. If missing or stale (> 24 hours), fetch from AniList
            if (!$animeData || $this->firebaseService->needsSync($animeData)) {
                Log::info("Anime {$id} missing or stale in Firebase. Fetching from AniList...");
                
                $aniListData = $this->aniListService->fetchAnimeById($id);

                if ($aniListData) {
                    // Prepare data for Firebase
                    $animeData = [
                        'anilist_id' => $aniListData['id'],
                        'title' => $aniListData['title'],
                        'description' => $aniListData['description'],
                        'format' => $aniListData['format'],
                        'status' => $aniListData['status'],
                        'episodes' => $aniListData['episodes'],
                        'duration' => $aniListData['duration'],
                        'average_score' => $aniListData['averageScore'],
                        'popularity' => $aniListData['popularity'],
                        'genres' => $aniListData['genres'],
                        'cover_image' => $aniListData['coverImage'],
                        'banner_image' => $aniListData['bannerImage'],
                        'trailer' => $aniListData['trailer'],
                        'studios' => array_map(fn($s) => $s['name'], $aniListData['studios']['nodes'] ?? []),
                        'views_count' => $animeData['views_count'] ?? 0, // Preserve views
                        'likes_count' => $animeData['likes_count'] ?? 0, // Preserve likes
                        'last_synced_at' => now()->toIso8601String(),
                        'updated_at' => now()->toIso8601String(),
                    ];

                    // 3. Save to Firebase
                    $this->firebaseService->saveAnime($id, $animeData);
                    $cached = false;
                    
                    // Also update local DB for listing purposes (optional but good for index())
                    Anime::updateOrCreate(
                        ['anilist_id' => $id],
                        [
                            'title_romaji' => $aniListData['title']['romaji'],
                            'cover_image_large' => $aniListData['coverImage']['large'],
                            'last_synced_at' => now(),
                        ]
                    );

                } else {
                    if (!$animeData) {
                        return response()->json(['error' => 'Anime not found on AniList'], 404);
                    }
                    Log::warning("Failed to refresh Anime {$id}. Serving stale data.");
                }
            }

            // 4. Increment views
            $this->firebaseService->incrementViews($id);
            $animeData['views_count'] = ($animeData['views_count'] ?? 0) + 1;

            return response()->json([
                'success' => true,
                'data' => $animeData,
                'cached' => $cached
            ]);

        } catch (\Exception $e) {
            Log::error('AnimeController Error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
