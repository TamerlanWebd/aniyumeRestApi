<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Anime extends Model
{
    use HasFactory;

    protected $table = 'anime';

    protected $fillable = [
        'anilist_id',
        'title_romaji',
        'title_english',
        'title_native',
        'description',
        'cover_image',
        'banner_image',
        'average_score',
        'popularity',
        'episodes',
        'type',
        'status',
        'season',
        'season_year',
        'start_date',
        'end_date',
        'streaming_episodes',
        'trailer_url',
        'is_adult',
        'country_of_origin',
        'anilist_updated_at',
    ];

    protected $casts = [
        'streaming_episodes' => 'array',
        'is_adult' => 'boolean',
        'average_score' => 'decimal:2',
        'anilist_updated_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    public function studios(): BelongsToMany
    {
        return $this->belongsToMany(Studio::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)
            ->withPivot('rank');
    }

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class)
            ->withPivot('role');
    }
}
