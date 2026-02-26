<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medias', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 255);
            $table->string('original_filename', 255);

            // media type: audio, image, video
            $table->enum('type', ['audio', 'image', 'video']);

            // original file extension (lowercase, without dot)
            $table->string('extension', 16)->nullable();

            // relative path inside media storage (e.g., movies/ironman.mp4)
            $table->string('storage_path', 255);

            // optional metadata
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable(); // bytes
            $table->integer('duration')->nullable(); // seconds (audio/video)
            $table->integer('width')->nullable(); // px (image/video)
            $table->integer('height')->nullable(); // px (image/video)

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('storage_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medias');
    }
};
