<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('anime_genre', function (Blueprint $table) {
            $table->foreignId('anime_id')->constrained('anime')->onDelete('cascade');
            $table->foreignId('genre_id')->constrained('genres')->onDelete('cascade');
            $table->primary(['anime_id', 'genre_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('anime_genre');
        Schema::dropIfExists('genres');
    }
};
