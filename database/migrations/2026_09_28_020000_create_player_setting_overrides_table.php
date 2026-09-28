<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_setting_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->string('setting_key', 100);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['player_id', 'setting_key'], 'player_setting_override_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_setting_overrides');
    }
};
