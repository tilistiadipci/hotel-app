<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->boolean('use_custom_content')->default(false)->after('is_active');
        });

        Schema::create('player_menu_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->string('menu_key', 50);
            $table->string('label', 100);
            $table->string('icon', 100)->nullable();
            $table->enum('placement', ['main', 'submenu'])->default('main');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['player_id', 'menu_key']);
            $table->index(['player_id', 'placement', 'sort_order'], 'player_menu_placement_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_menu_settings');

        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('use_custom_content');
        });
    }
};
