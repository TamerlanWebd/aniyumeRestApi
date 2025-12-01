<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anime', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('anilist_id')->unique();
            $table->string('title_romaji')->index();
            $table->string('title_english')->nullable()->index();
            $table->string('title_native')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('banner_image')->nullable();
            $table->decimal('average_score', 5, 2)->nullable()->index();
            $table->integer('popularity')->default(0)->index();
            $table->integer('episodes')->nullable();
            $table->enum('type', ['TV', 'TV_SHORT', 'MOVIE', 'OVA', 'ONA', 'SPECIAL', 'MUSIC'])->index();
            $table->enum('status', ['FINISHED', 'RELEASING', 'NOT_YET_RELEASED', 'CANCELLED'])->index();
            $table->string('season')->nullable();
            $table->integer('season_year')->nullable()->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('streaming_episodes')->nullable();
            $table->string('trailer_url')->nullable();
            $table->boolean('is_adult')->default(false)->index();
            $table->string('country_of_origin', 2)->nullable();
            $table->timestamps();
            $table->timestamp('anilist_updated_at')->nullable();
            
            // Full-text search (PostgreSQL specific or standard index fallback)
            if (config('database.default') === 'pgsql') {
                // We will add the index via raw SQL after table creation to ensure it works
            } else {
                // Fallback for other drivers (like SQLite)
                $table->index(['title_romaji', 'title_english']);
            }
        });

        if (config('database.default') === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement("CREATE INDEX anime_fulltext_idx ON anime USING GIN (to_tsvector('english', title_romaji || ' ' || COALESCE(title_english, '') || ' ' || COALESCE(description, '')))");
        }
    }

    public function down(): void
    {
        if (config('database.default') === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS anime_fulltext_idx');
        }
        Schema::dropIfExists('anime');
    }
};
