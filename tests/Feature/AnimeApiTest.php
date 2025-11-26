<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User; // Assuming User model exists

class AnimeApiTest extends TestCase
{
    // use RefreshDatabase; // Not using SQL DB for anime, so strictly speaking not needed for Firestore, but good for User auth

    /**
     * Test getting anime list (V1).
     */
    public function test_can_get_anime_list_v1(): void
    {
        $response = $this->getJson('/api/v1/anime');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'title', 'genre']
                     ]
                 ]);
    }

    /**
     * Test getting anime list (V2).
     */
    public function test_can_get_anime_list_v2(): void
    {
        $response = $this->getJson('/api/v2/anime');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'meta' => ['version', 'cached'],
                     'links'
                 ]);
    }

    /**
     * Test creating anime requires auth.
     */
    public function test_create_anime_requires_auth(): void
    {
        $response = $this->postJson('/api/anime', [
            'title' => 'Test Anime',
            'genre' => 'Action',
        ]);

        $response->assertStatus(401); // Unauthenticated
    }

    /**
     * Test health check.
     */
    public function test_health_check(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'services']);
    }
}
