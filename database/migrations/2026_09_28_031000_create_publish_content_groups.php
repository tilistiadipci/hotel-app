<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publish_content_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('hotel_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
            $table->unique(['hotel_id', 'name'], 'publish_content_group_hotel_name_unique');
        });

        Schema::create('publish_content_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publish_content_group_id')->constrained('publish_content_groups')->cascadeOnDelete();
            $table->string('content_type', 30);
            $table->string('mode', 20)->default('all');
            $table->unsignedBigInteger('content_id')->nullable();
            $table->timestamps();

            $table->index(['content_type', 'mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publish_content_group_items');
        Schema::dropIfExists('publish_content_groups');
    }
};
