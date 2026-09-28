<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publish_menu_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('hotel_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
            $table->unique(['hotel_id', 'name'], 'publish_menu_group_hotel_name_unique');
        });

        Schema::create('publish_menu_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publish_menu_group_id')->constrained('publish_menu_groups')->cascadeOnDelete();
            $table->string('menu_key', 50);
            $table->string('label', 100);
            $table->string('icon', 100)->nullable();
            $table->string('placement', 20)->default('main');
            $table->string('parent_menu_key', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['publish_menu_group_id', 'menu_key'], 'publish_menu_group_item_unique');
        });

        Schema::create('publish_channel_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('hotel_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
            $table->unique(['hotel_id', 'name'], 'publish_channel_group_hotel_name_unique');
        });

        Schema::create('publish_channel_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publish_channel_group_id')->constrained('publish_channel_groups')->cascadeOnDelete();
            $table->foreignId('tv_channel_id')->constrained('tv_channels')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['publish_channel_group_id', 'tv_channel_id'], 'publish_channel_group_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publish_channel_group_items');
        Schema::dropIfExists('publish_channel_groups');
        Schema::dropIfExists('publish_menu_group_items');
        Schema::dropIfExists('publish_menu_groups');
    }
};
