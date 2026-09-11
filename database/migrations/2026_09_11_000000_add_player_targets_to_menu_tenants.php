<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_tenants', function (Blueprint $table) {
            $table->string('target_mode', 20)->default('all')->after('is_active');
        });

        Schema::create('menu_tenant_player_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_tenant_id')->constrained('menu_tenants')->cascadeOnDelete();
            $table->foreignId('player_group_id')->constrained('player_groups')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['menu_tenant_id', 'player_group_id'], 'tenant_player_group_unique');
        });

        Schema::create('menu_tenant_player', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_tenant_id')->constrained('menu_tenants')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['menu_tenant_id', 'player_id'], 'tenant_player_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_tenant_player');
        Schema::dropIfExists('menu_tenant_player_group');

        Schema::table('menu_tenants', function (Blueprint $table) {
            $table->dropColumn('target_mode');
        });
    }
};
