<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnimeCreated
{
    use Dispatchable, SerializesModels;

    public $anime;

    public function __construct($anime)
    {
        $this->anime = $anime;
    }
}
