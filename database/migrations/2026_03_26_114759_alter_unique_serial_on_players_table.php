<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            // drop unique lama
            $table->dropUnique('players_serial_unique');

            // tambah composite unique (serial + deleted_at)
            $table->unique(['serial', 'deleted_at'], 'players_serial_deleted_at_unique');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            // drop composite
            $table->dropUnique('players_serial_deleted_at_unique');

            // balikin ke unique lama
            $table->unique('serial', 'players_serial_unique');
        });
    }
};
