<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class AnimeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'] ?? null,
            'title' => $this['title'] ?? '',
            'slug' => Str::slug($this['title'] ?? ''),
            'genre' => $this['genre'] ?? '',
            'description' => $this['description'] ?? '',
            'image_url' => $this['imageUrl'] ?? '', // Mapping imageUrl to snake_case if preferred, or keeping camelCase
            // 'rating' => 0, // Placeholder for future logic
            // 'views_count' => 0, // Placeholder
            'links' => [
                'self' => url("/api/anime/{$this['id']}"),
            ],
        ];
    }
}
