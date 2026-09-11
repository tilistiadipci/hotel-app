<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->index(['hotel_id', 'deleted_at'], 'songs_hotel_id_deleted_at_index');
            $table->index(['hotel_id', 'is_active'], 'songs_hotel_id_is_active_index');
        });
    }

    public function down(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->dropIndex('songs_hotel_id_deleted_at_index');
            $table->dropIndex('songs_hotel_id_is_active_index');
        });
    }
};
