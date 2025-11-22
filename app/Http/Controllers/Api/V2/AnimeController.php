<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Services\FirestoreRestService;
use App\Http\Resources\AnimeResource;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="Anime V2",
 *     description="Advanced API Endpoints with Caching"
 * )
 */
class AnimeController extends Controller
{
    protected $firestore;
    protected $collectionName = 'anime';

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    /**
     * @OA\Get(
     *      path="/api/v2/anime",
     *      operationId="getAnimeListV2",
     *      tags={"Anime V2"},
     *      summary="Get list of anime (Cached)",
     *      description="Returns list of anime with caching",
     *      @OA\Parameter(name="page", in="query", description="Page number", required=false, @OA\Schema(type="integer")),
     *      @OA\Parameter(name="limit", in="query", description="Items per page", required=false, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $limit;

        $cacheKey = "anime:v2:list:page_{$page}:limit_{$limit}";
        
        $animeList = Cache::remember($cacheKey, 300, function () use ($limit, $offset) {
            return $this->firestore->collection($this->collectionName)->list($limit, $offset);
        });

        return AnimeResource::collection(collect($animeList))->additional([
            'meta' => [
                'version' => 'v2',
                'page' => (int)$page,
                'limit' => (int)$limit,
                'cached' => Cache::has($cacheKey),
            ],
            'links' => [
                'self' => url()->current(),
                'next' => $page > 1 ? url()->current() . '?page=' . ($page + 1) : null,
                'prev' => $page > 1 ? url()->current() . '?page=' . ($page - 1) : null,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/anime/{id}",
     *      operationId="getAnimeByIdV2",
     *      tags={"Anime V2"},
     *      summary="Get anime by ID (Cached)",
     *      description="Returns anime detail",
     *      @OA\Parameter(name="id", in="path", description="Anime ID", required=true, @OA\Schema(type="string")),
     *      @OA\Response(response=200, description="Success"),
     *      @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show($id)
    {
        $cacheKey = "anime:v2:detail:{$id}";

        $doc = Cache::remember($cacheKey, 300, function () use ($id) {
            return $this->firestore->collection($this->collectionName)->get($id);
        });

        if (!$doc) {
            return response()->json(['error' => 'Anime not found'], 404);
        }

        return new AnimeResource($doc);
    }
}
