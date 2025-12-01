<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Studio extends Model
{
    use HasFactory;

    protected $fillable = ['anilist_id', 'name'];

    public function anime(): BelongsToMany
    {
        return $this->belongsToMany(Anime::class);
    }
}
