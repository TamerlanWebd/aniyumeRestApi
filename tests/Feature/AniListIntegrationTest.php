<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Anime;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class AniListIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fetches_from_anilist_if_not_in_db()
    {
        // Mock AniList response
        Http::fake([
            'graphql.anilist.co' => Http::response([
                'data' => [
                    'Media' => [
                        'id' => 21,
                        'title' => ['romaji' => 'One Piece', 'english' => 'One Piece', 'native' => 'ONE PIECE'],
                        'description' => 'Pirates...',
                        'coverImage' => ['large' => 'url', 'medium' => 'url'],
                        'bannerImage' => 'url',
                        'format' => 'TV',
                        'status' => 'RELEASING',
                        'episodes' => 1000,
                        'duration' => 24,
                        'genres' => ['Action', 'Adventure'],
                        'averageScore' => 90,
                        'popularity' => 100000,
                    ]
                ]
            ], 200),
        ]);

        $response = $this->getJson('/api/v2/anime/21');

        $response->assertStatus(200)
            ->assertJsonPath('data.title_romaji', 'One Piece');

        $this->assertDatabaseHas('animes', [
            'anilist_id' => 21,
            'title_romaji' => 'One Piece',
        ]);
    }

    public function test_it_returns_from_db_if_fresh()
    {
        // Seed DB
        Anime::create([
            'anilist_id' => 21,
            'title_romaji' => 'One Piece DB',
            'last_synced_at' => now(),
        ]);

        // Mock Http to ensure it's NOT called (or if called, we'd see different data)
        Http::fake([
            'graphql.anilist.co' => Http::response(['data' => ['Media' => ['title' => ['romaji' => 'One Piece API']]]], 200),
        ]);

        $response = $this->getJson('/api/v2/anime/21');

        $response->assertStatus(200)
            ->assertJsonPath('data.title_romaji', 'One Piece DB');
    }

    public function test_it_refreshes_from_anilist_if_stale()
    {
        // Seed DB with stale data
        Anime::create([
            'anilist_id' => 21,
            'title_romaji' => 'One Piece Old',
            'last_synced_at' => now()->subDays(2),
        ]);

        // Mock AniList response
        Http::fake([
            'graphql.anilist.co' => Http::response([
                'data' => [
                    'Media' => [
                        'id' => 21,
                        'title' => ['romaji' => 'One Piece New', 'english' => 'One Piece', 'native' => 'ONE PIECE'],
                        'description' => 'Pirates...',
                        'coverImage' => ['large' => 'url', 'medium' => 'url'],
                        'bannerImage' => 'url',
                        'format' => 'TV',
                        'status' => 'RELEASING',
                        'episodes' => 1000,
                        'duration' => 24,
                        'genres' => ['Action', 'Adventure'],
                        'averageScore' => 90,
                        'popularity' => 100000,
                    ]
                ]
            ], 200),
        ]);

        $response = $this->getJson('/api/v2/anime/21');

        $response->assertStatus(200)
            ->assertJsonPath('data.title_romaji', 'One Piece New');

        $this->assertDatabaseHas('animes', [
            'anilist_id' => 21,
            'title_romaji' => 'One Piece New',
        ]);
    }
}
