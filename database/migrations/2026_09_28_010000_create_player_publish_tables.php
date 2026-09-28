<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('player_content_scope_items');
        Schema::dropIfExists('player_content_scopes');
        Schema::dropIfExists('player_publish_targets');
        Schema::dropIfExists('player_publishes');

        Schema::create('player_publishes', function (Blueprint $table) {
            $table->id();
            $table->uuid('hotel_id');
            $table->string('name')->nullable();
            $table->foreignId('theme_id')->nullable()->constrained('themes')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
            $table->index(['hotel_id', 'published_at']);
        });

        Schema::create('player_publish_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_publish_id')->constrained('player_publishes')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['player_publish_id', 'player_id'], 'publish_target_unique');
        });

        Schema::create('player_content_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->string('content_type', 30);
            $table->string('mode', 20)->default('all');
            $table->timestamps();

            $table->unique(['player_id', 'content_type'], 'player_content_scope_unique');
            $table->index(['content_type', 'mode']);
        });

        Schema::create('player_content_scope_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_content_scope_id')->constrained('player_content_scopes')->cascadeOnDelete();
            $table->unsignedBigInteger('content_id');
            $table->timestamps();

            $table->unique(['player_content_scope_id', 'content_id'], 'player_content_scope_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_content_scope_items');
        Schema::dropIfExists('player_content_scopes');
        Schema::dropIfExists('player_publish_targets');
        Schema::dropIfExists('player_publishes');
    }
};
