<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movie_category_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained('movies')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('movies_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['movie_id', 'category_id'], 'unique_movie_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movie_category_relations');
    }
};
