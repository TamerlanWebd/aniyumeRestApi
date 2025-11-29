<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Anime extends Model
{
    protected $fillable = [
        'anilist_id',
        'title_romaji',
        'title_english',
        'title_native',
        'description',
        'cover_image_large',
        'cover_image_medium',
        'banner_image',
        'format',
        'status',
        'episodes',
        'duration',
        'genres',
        'average_score',
        'popularity',
        'last_synced_at',
    ];

    protected $casts = [
        'genres' => 'array',
        'last_synced_at' => 'datetime',
    ];
}
