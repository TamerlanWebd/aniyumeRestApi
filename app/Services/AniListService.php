<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AniListService
{
    protected $endpoint = 'https://graphql.anilist.co';

    public function fetchAnimeById($id)
    {
        $query = '
        query ($id: Int) {
            Media (id: $id, type: ANIME) {
                id
                title {
                    romaji
                    english
                    native
                }
                description
                format
                status
                episodes
                duration
                averageScore
                popularity
                genres
                coverImage {
                    large
                    medium
                }
                bannerImage
                trailer {
                    id
                    site
                }
                studios {
                    nodes {
                        name
                    }
                }
            }
        }
        ';

        $variables = [
            'id' => $id
        ];

        try {
            $response = Http::post($this->endpoint, [
                'query' => $query,
                'variables' => $variables,
            ]);

            if ($response->failed()) {
                Log::error('AniList API Error: ' . $response->body());
                return null;
            }

            $data = $response->json();

            if (isset($data['errors'])) {
                Log::error('AniList GraphQL Error: ' . json_encode($data['errors']));
                return null;
            }

            return $data['data']['Media'] ?? null;

        } catch (\Exception $e) {
            Log::error('AniList Service Exception: ' . $e->getMessage());
            return null;
        }
    }
}
