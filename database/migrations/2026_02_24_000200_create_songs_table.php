<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('artist_id')->constrained('artists')->cascadeOnDelete();
            $table->foreignId('album_id')->nullable()->constrained('albums')->nullOnDelete();
            $table->string('title', 200);
            $table->foreignId('song_id')->nullable()->constrained('medias')->nullOnDelete(); // audio file in medias
            $table->integer('duration')->default(0); // seconds
            $table->foreignId('image_id')->nullable()->constrained('medias')->nullOnDelete(); // cover image from medias
            $table->integer('sort_order')->nullable(); // for album songs
            $table->boolean('is_active')->default(true);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};
