<?php

namespace App\Services;

use App\Models\Anime;
use App\Models\Genre;
use App\Models\Studio;
use App\Models\Tag;
use App\Models\Character;
use App\Models\SyncUpdate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AniListSyncService
{
    private const API_URL = 'https://graphql.anilist.co';
    private const ITEMS_PER_PAGE = 50;

    public function syncAllAnime(int $page = 1): void
    {
        // Disable audit logging during sync to prevent memory exhaustion
        \App\Models\Anime::unsetEventDispatcher();
        \App\Models\Genre::unsetEventDispatcher();
        \App\Models\Studio::unsetEventDispatcher();
        \App\Models\Tag::unsetEventDispatcher();
        \App\Models\Character::unsetEventDispatcher();
        \App\Models\SyncUpdate::unsetEventDispatcher();
        
        $query = '
            query ($page: Int, $perPage: Int) {
                Page(page: $page, perPage: $perPage) {
                    pageInfo {
                        total
                        currentPage
                        lastPage
                        hasNextPage
                    }
                    media(type: ANIME, sort: POPULARITY_DESC) {
                        id
                        title {
                            romaji
                            english
                            native
                        }
                        description
                        coverImage {
                            large
                        }
                        bannerImage
                        averageScore
                        popularity
                        episodes
                        format
                        status
                        season
                        seasonYear
                        startDate {
                            year
                            month
                            day
                        }
                        endDate {
                            year
                            month
                            day
                        }
                        streamingEpisodes {
                            title
                            thumbnail
                            url
                        }
                        trailer {
                            id
                            site
                        }
                        isAdult
                        countryOfOrigin
                        genres
                        tags {
                            id
                            name
                            description
                            category
                            rank
                            isAdult
                        }
                        studios(isMain: true) {
                            nodes {
                                id
                                name
                            }
                        }
                        characters(sort: ROLE, perPage: 10) {
                            nodes {
                                id
                                name {
                                    full
                                    native
                                }
                                description
                                image {
                                    large
                                }
                            }
                            edges {
                                role
                            }
                        }
                        updatedAt
                    }
                }
            }
        ';

        $variables = [
            'page' => $page,
            'perPage' => self::ITEMS_PER_PAGE,
        ];

        $response = Http::post(self::API_URL, [
            'query' => $query,
            'variables' => $variables,
        ]);

        if ($response->failed()) {
            Log::error('AniList API failed', ['response' => $response->body()]);
            return;
        }

        $data = $response->json();
        $pageInfo = $data['data']['Page']['pageInfo'];
        $mediaList = $data['data']['Page']['media'];

        foreach ($mediaList as $mediaData) {
            $this->processAnime($mediaData);
        }

        Log::info("Synced page {$page} of {$pageInfo['lastPage']}");

        // Continue to next page
        if ($pageInfo['hasNextPage']) {
            sleep(1); // Rate limiting
            $this->syncAllAnime($page + 1);
        }
    }

    private function processAnime(array $data): void
    {
        $hash = md5(json_encode($data));
        
        $syncRecord = SyncUpdate::where('anilist_id', $data['id'])->first();
        
        if ($syncRecord && $syncRecord->hash === $hash) {
            return; // No changes
        }

        $anime = Anime::updateOrCreate(
            ['anilist_id' => $data['id']],
            [
                'title_romaji' => $data['title']['romaji'],
                'title_english' => $data['title']['english'],
                'title_native' => $data['title']['native'],
                'description' => strip_tags($data['description'] ?? ''),
                'cover_image' => $data['coverImage']['large'] ?? null,
                'banner_image' => $data['bannerImage'] ?? null,
                'average_score' => $data['averageScore'] ?? null,
                'popularity' => $data['popularity'] ?? 0,
                'episodes' => $data['episodes'] ?? null,
                'type' => $data['format'] ?? 'TV',
                'status' => $data['status'] ?? 'NOT_YET_RELEASED',
                'season' => $data['season'] ?? null,
                'season_year' => $data['seasonYear'] ?? null,
                'start_date' => $this->formatDate($data['startDate'] ?? null),
                'end_date' => $this->formatDate($data['endDate'] ?? null),
                'streaming_episodes' => $data['streamingEpisodes'] ?? null,
                'trailer_url' => $this->formatTrailerUrl($data['trailer'] ?? null),
                'is_adult' => $data['isAdult'] ?? false,
                'country_of_origin' => $data['countryOfOrigin'] ?? 'JP',
                'anilist_updated_at' => now()->timestamp($data['updatedAt']),
            ]
        );

        // Sync genres
        if (!empty($data['genres'])) {
            $genreIds = [];
            foreach ($data['genres'] as $genreName) {
                $genre = Genre::firstOrCreate(
                    ['name' => $genreName],
                    ['slug' => Str::slug($genreName)]
                );
                $genreIds[] = $genre->id;
            }
            $anime->genres()->sync($genreIds);
        }

        // Sync studios
        if (!empty($data['studios']['nodes'])) {
            $studioIds = [];
            foreach ($data['studios']['nodes'] as $studioData) {
                $studio = Studio::firstOrCreate(
                    ['anilist_id' => $studioData['id']],
                    ['name' => $studioData['name']]
                );
                $studioIds[] = $studio->id;
            }
            $anime->studios()->sync($studioIds);
        }

        // Sync tags
        if (!empty($data['tags'])) {
            $tagSyncData = [];
            foreach ($data['tags'] as $tagData) {
                $tag = Tag::firstOrCreate(
                    ['anilist_id' => $tagData['id']],
                    [
                        'name' => $tagData['name'],
                        'description' => $tagData['description'] ?? null,
                        'category' => $tagData['category'] ?? null,
                        'is_adult' => $tagData['isAdult'] ?? false,
                    ]
                );
                $tagSyncData[$tag->id] = ['rank' => $tagData['rank'] ?? null];
            }
            $anime->tags()->sync($tagSyncData);
        }

        // Sync characters
        if (!empty($data['characters']['nodes'])) {
            $characterSyncData = [];
            foreach ($data['characters']['nodes'] as $index => $charData) {
                $character = Character::firstOrCreate(
                    ['anilist_id' => $charData['id']],
                    [
                        'name_full' => $charData['name']['full'],
                        'name_native' => $charData['name']['native'] ?? null,
                        'description' => strip_tags($charData['description'] ?? ''),
                        'image' => $charData['image']['large'] ?? null,
                    ]
                );
                $role = $data['characters']['edges'][$index]['role'] ?? 'SUPPORTING';
                $characterSyncData[$character->id] = ['role' => $role];
            }
            $anime->characters()->sync($characterSyncData);
        }

        // Update sync record
        SyncUpdate::updateOrCreate(
            ['anilist_id' => $data['id']],
            [
                'hash' => $hash,
                'last_synced' => now(),
            ]
        );

        Log::info("Synced anime: {$anime->title_romaji}");
    }

    private function formatDate(?array $date): ?string
    {
        if (!$date || !isset($date['year'])) {
            return null;
        }

        $year = $date['year'];
        $month = $date['month'] ?? 1;
        $day = $date['day'] ?? 1;

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    private function formatTrailerUrl(?array $trailer): ?string
    {
        if (!$trailer || !isset($trailer['id'])) {
            return null;
        }

        return $trailer['site'] === 'youtube'
            ? "https://www.youtube.com/watch?v={$trailer['id']}"
            : null;
    }
}
