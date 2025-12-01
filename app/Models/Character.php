<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Character extends Model
{
    use HasFactory;

    protected $fillable = [
        'anilist_id',
        'name_full',
        'name_native',
        'description',
        'image',
    ];

    public function anime(): BelongsToMany
    {
        return $this->belongsToMany(Anime::class)
            ->withPivot('role');
    }
}
