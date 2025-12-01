<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('anilist_id')->unique();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_adult')->default(false);
            $table->timestamps();
        });

        Schema::create('anime_tag', function (Blueprint $table) {
            $table->foreignId('anime_id')->constrained('anime')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');
            $table->integer('rank')->nullable();
            $table->primary(['anime_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anime_tag');
        Schema::dropIfExists('tags');
    }
};
