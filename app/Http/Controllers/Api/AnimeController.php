<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\AnimeRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnimeController extends Controller
{
    public function __construct(
        private AnimeRepository $animeRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'genres',
            'type',
            'status',
            'year',
            'season',
            'is_adult',
            'min_score',
            'sort',
            'order',
        ]);

        $perPage = $request->input('per_page', 20);

        $anime = $this->animeRepository->search($filters, $perPage);

        return response()->json($anime);
    }

    public function show(int $anilistId): JsonResponse
    {
        $anime = $this->animeRepository->findByAnilistId($anilistId);

        if (!$anime) {
            return response()->json(['message' => 'Anime not found'], 404);
        }

        return response()->json($anime);
    }

    public function popular(): JsonResponse
    {
        $anime = $this->animeRepository->getPopular();

        return response()->json($anime);
    }

    public function trending(): JsonResponse
    {
        $anime = $this->animeRepository->getTrending();

        return response()->json($anime);
    }
}
