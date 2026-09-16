<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_menu_settings', function (Blueprint $table) {
            $table->string('icon_path', 500)->nullable()->after('icon');
            $table->string('parent_menu_key', 50)->nullable()->after('placement');
            $table->index(['player_id', 'parent_menu_key'], 'player_menu_parent_index');
        });
    }

    public function down(): void
    {
        Schema::table('player_menu_settings', function (Blueprint $table) {
            $table->dropIndex('player_menu_parent_index');
            $table->dropColumn(['icon_path', 'parent_menu_key']);
        });
    }
};
