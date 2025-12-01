<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('anilist_id')->unique();
            $table->string('name_full')->index();
            $table->string('name_native')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('anime_character', function (Blueprint $table) {
            $table->foreignId('anime_id')->constrained('anime')->onDelete('cascade');
            $table->foreignId('character_id')->constrained('characters')->onDelete('cascade');
            $table->enum('role', ['MAIN', 'SUPPORTING', 'BACKGROUND']);
            $table->primary(['anime_id', 'character_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anime_character');
        Schema::dropIfExists('characters');
    }
};
