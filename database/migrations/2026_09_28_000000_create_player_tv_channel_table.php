<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->boolean('use_custom_channels')->default(false)->after('use_custom_content');
        });

        Schema::create('player_tv_channel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('tv_channel_id')->constrained('tv_channels')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['player_id', 'tv_channel_id']);
            $table->index(['player_id', 'is_active', 'sort_order'], 'player_tv_channel_active_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_tv_channel');

        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('use_custom_channels');
        });
    }
};
