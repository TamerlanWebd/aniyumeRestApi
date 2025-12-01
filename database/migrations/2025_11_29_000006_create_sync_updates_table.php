<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_updates', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('anilist_id')->unique();
            $table->string('hash')->nullable();
            $table->timestamp('last_synced');
            $table->timestamps();
            
            $table->index('last_synced');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_updates');
    }
};
