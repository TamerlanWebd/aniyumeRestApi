<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studios', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('anilist_id')->unique();
            $table->string('name')->index();
            $table->timestamps();
        });

        Schema::create('anime_studio', function (Blueprint $table) {
            $table->foreignId('anime_id')->constrained('anime')->onDelete('cascade');
            $table->foreignId('studio_id')->constrained('studios')->onDelete('cascade');
            $table->primary(['anime_id', 'studio_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('anime_studio');
        Schema::dropIfExists('studios');
    }
};
