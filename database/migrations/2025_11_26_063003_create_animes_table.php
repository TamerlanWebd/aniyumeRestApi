<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('animes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anilist_id')->unique()->nullable();
            $table->string('title_romaji')->nullable();
            $table->string('title_english')->nullable();
            $table->string('title_native')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_image_large')->nullable();
            $table->string('cover_image_medium')->nullable();
            $table->string('banner_image')->nullable();
            $table->string('format')->nullable();
            $table->string('status')->nullable();
            $table->integer('episodes')->nullable();
            $table->integer('duration')->nullable();
            $table->json('genres')->nullable();
            $table->integer('average_score')->nullable();
            $table->integer('popularity')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animes');
    }
};
