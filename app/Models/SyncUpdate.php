<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'anilist_id',
        'hash',
        'last_synced',
    ];

    protected $casts = [
        'last_synced' => 'datetime',
    ];
}
