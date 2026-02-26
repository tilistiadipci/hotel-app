<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('medias')->nullOnDelete();
            $table->foreignId('video_id')->nullable()->constrained('medias')->nullOnDelete();
            $table->integer('duration'); // seconds
            $table->date('release_date')->nullable();
            $table->string('rating', 10)->nullable();
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
        Schema::dropIfExists('movies');
    }
};
