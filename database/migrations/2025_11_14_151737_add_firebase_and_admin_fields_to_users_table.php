<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // если колонки ещё нет – добавить
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'firebase_uid')) {
                $table->string('firebase_uid')->nullable()->unique();
            }
        });
    }

    public function down(): void
    {
        // ВНИМАНИЕ: SQLite плохо умеет dropColumn.
        // Чтобы не ронять migrate:refresh, просто ничего не делаем,
        // либо делаем безопасную проверку:

        if (Schema::hasColumn('users', 'firebase_uid')) {
            Schema::table('users', function (Blueprint $table) {
                // На SQLite dropColumn часто ломается.
                // Вариант №1 — вообще не дропать колонку:
                // $table->dropColumn('firebase_uid');

                // Вариант №2 — оставляем колонку, но не роняем миграцию.
            });
        }
    }
};
