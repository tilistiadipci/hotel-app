<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_channel_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tv_channel_id')->constrained('tv_channels')->cascadeOnDelete();
            $table->string('label', 100)->nullable();
            $table->text('stream_url');
            $table->longText('entry_text')->nullable();
            $table->char('source_hash', 64)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tv_channel_id', 'is_active', 'sort_order'], 'tv_channel_sources_active_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_channel_sources');
    }
};
