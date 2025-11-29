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
        'anilist_id' => $this['anilist_id'] ?? null,  // ← Добавь это
        'title_romaji' => $this['title_romaji'] ?? null,  // ← И это (главное!)
        'title_english' => $this['title_english'] ?? null,  // Если нужно
        'title' => $this['title'] ?? '',  // Оставь старое, если нужно
        'slug' => Str::slug($this['title'] ?? ''),
        'genre' => $this['genre'] ?? '',
        'description' => $this['description'] ?? '',
        'image_url' => $this['imageUrl'] ?? '',
        'links' => [
            'self' => url("/api/anime/{$this['id']}"),
        ],
    ];
}

}
