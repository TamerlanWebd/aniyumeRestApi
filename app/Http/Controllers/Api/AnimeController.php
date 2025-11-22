<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Services\FirestoreRestService;
use App\Http\Resources\AnimeResource;

/**
 * @OA\Tag(
 *     name="Anime",
 *     description="API Endpoints of Anime"
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
     *      path="/api/anime",
     *      operationId="getAnimeList",
     *      tags={"Anime"},
     *      summary="Get list of anime",
     *      description="Returns list of anime",
     *      @OA\Parameter(
     *          name="page",
     *          in="query",
     *          description="Page number",
     *          required=false,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Parameter(
     *          name="limit",
     *          in="query",
     *          description="Items per page",
     *          required=false,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $limit;

        $animeList = $this->firestore->collection($this->collectionName)->list($limit, $offset);

        return AnimeResource::collection(collect($animeList))->additional([
            'meta' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
            ]
        ]);
    }

    public function show($id)
    {
        $doc = $this->firestore->collection($this->collectionName)->get($id);

        if (!$doc) {
            return response()->json(['error' => 'Anime not found'], 404);
        }

        return new AnimeResource($doc);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'genre' => 'required|string',
            'description' => 'required|string',
            'imageUrl' => 'required|string',
        ]);

        $doc = $this->firestore->collection($this->collectionName)->add($data);

        return new AnimeResource($doc);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'title' => 'sometimes|string',
            'genre' => 'sometimes|string',
            'description' => 'sometimes|string',
            'imageUrl' => 'sometimes|string',
        ]);

        if (empty($data)) {
            return response()->json(['message' => 'No data to update'], 400);
        }

        $doc = $this->firestore->collection($this->collectionName)->update($id, $data);

        if ($doc === false) {
            return response()->json(['error' => 'Anime not found'], 404);
        }

        return new AnimeResource($doc);
    }

    public function destroy($id)
    {
        $success = $this->firestore->collection($this->collectionName)->delete($id);
        
        if (!$success) {
             return response()->json(['error' => 'Failed to delete'], 500);
        }

        return response()->json(['message' => 'Deleted successfully']);
    }
}
