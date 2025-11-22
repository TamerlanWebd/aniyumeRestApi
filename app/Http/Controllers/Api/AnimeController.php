<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Services\FirestoreRestService;

class AnimeController extends Controller
{
    protected $firestore;
    protected $collectionName = 'anime';

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $limit;

        // Note: FirestoreRestService implements a simplified list method
        // that currently only supports limit (pageSize). 
        // Full offset pagination would require more complex cursor logic.
        $animeList = $this->firestore->collection($this->collectionName)->list($limit, $offset);

        return response()->json([
            'data' => $animeList,
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

        return response()->json($doc);
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

        return response()->json($doc, 201);
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

        return response()->json(['id' => $id, 'message' => 'Updated successfully']);
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
