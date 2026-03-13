<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->boolean('is_favorit')->default(false)->after('is_active');
        });

        Schema::table('songs', function (Blueprint $table) {
            $table->boolean('is_favorit')->default(false)->after('is_active');
        });

        Schema::table('places', function (Blueprint $table) {
            $table->boolean('is_favorit')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn('is_favorit');
        });

        Schema::table('songs', function (Blueprint $table) {
            $table->dropColumn('is_favorit');
        });

        Schema::table('places', function (Blueprint $table) {
            $table->dropColumn('is_favorit');
        });
    }
};
