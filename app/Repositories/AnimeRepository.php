<?php

namespace App\Repositories;

use App\Models\Anime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AnimeRepository
{
    public function search(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Anime::query()->with(['genres', 'studios']);

        // Full-text search
        if (!empty($filters['search'])) {
            $query->whereFullText(
                ['title_romaji', 'title_english', 'description'],
                $filters['search']
            );
        }

        // Filter by genres
        if (!empty($filters['genres'])) {
            $query->whereHas('genres', function ($q) use ($filters) {
                $q->whereIn('slug', $filters['genres']);
            });
        }

        // Filter by type
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by year
        if (!empty($filters['year'])) {
            $query->where('season_year', $filters['year']);
        }

        // Filter by season
        if (!empty($filters['season'])) {
            $query->where('season', $filters['season']);
        }

        // Filter adult content
        if (isset($filters['is_adult'])) {
            $query->where('is_adult', (bool) $filters['is_adult']);
        }

        // Minimum score
        if (!empty($filters['min_score'])) {
            $query->where('average_score', '>=', $filters['min_score']);
        }

        // Sorting
        $sortBy = $filters['sort'] ?? 'popularity';
        $sortOrder = $filters['order'] ?? 'desc';

        switch ($sortBy) {
            case 'rating':
                $query->orderBy('average_score', $sortOrder);
                break;
            case 'title':
                $query->orderBy('title_romaji', $sortOrder);
                break;
            case 'date':
                $query->orderBy('start_date', $sortOrder);
                break;
            case 'popularity':
            default:
                $query->orderBy('popularity', $sortOrder);
                break;
        }

        return $query->paginate($perPage);
    }

    public function findByAnilistId(int $anilistId): ?Anime
    {
        return Anime::with(['genres', 'studios', 'tags', 'characters'])
            ->where('anilist_id', $anilistId)
            ->first();
    }

    public function getPopular(int $limit = 10): Collection
    {
        return Anime::with(['genres', 'studios'])
            ->orderBy('popularity', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getTrending(int $limit = 10): Collection
    {
        return Anime::with(['genres', 'studios'])
            ->where('status', 'RELEASING')
            ->orderBy('popularity', 'desc')
            ->limit($limit)
            ->get();
    }
}
