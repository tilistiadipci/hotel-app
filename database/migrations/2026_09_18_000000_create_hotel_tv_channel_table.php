<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tv_channels MODIFY stream_url TEXT NULL');

        Schema::table('tv_channels', function (Blueprint $table) {
            $table->string('tvg_id', 180)->nullable()->after('slug')->index();
            $table->string('group_title', 150)->nullable()->after('tvg_id')->index();
            $table->string('source_type', 20)->default('manual')->after('group_title');
            $table->string('source_logo_url', 1000)->nullable()->after('source_type');
            $table->char('source_hash', 64)->nullable()->after('source_logo_url')->index();
        });

        Schema::create('hotel_tv_channel', function (Blueprint $table) {
            $table->id();
            $table->uuid('hotel_id');
            $table->unsignedBigInteger('tv_channel_id');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('custom_name', 150)->nullable();
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
            $table->foreign('tv_channel_id')->references('id')->on('tv_channels')->cascadeOnDelete();
            $table->unique(['hotel_id', 'tv_channel_id']);
            $table->index(['hotel_id', 'is_active', 'sort_order'], 'hotel_tv_channel_active_sort_index');
        });

        $masterId = DB::table('hotels')->where('is_system', true)->value('id');
        $hotels = DB::table('hotels')->where('is_system', false)->whereNull('deleted_at')->pluck('id');

        foreach ($hotels as $hotelId) {
            $channels = DB::table('tv_channels')->where('hotel_id', $hotelId)->whereNull('deleted_at')->get();

            foreach ($channels as $channel) {
                $masterChannelId = $masterId
                    ? DB::table('tv_channels')->where('hotel_id', $masterId)->where('slug', $channel->slug)->whereNull('deleted_at')->value('id')
                    : null;
                $assignedChannelId = $masterChannelId ?: $channel->id;

                DB::table('hotel_tv_channel')->updateOrInsert(
                    ['hotel_id' => $hotelId, 'tv_channel_id' => $assignedChannelId],
                    [
                        'is_active' => (bool) $channel->is_active,
                        'sort_order' => (int) $channel->sort_order,
                        'custom_name' => $masterChannelId && $channel->name !== DB::table('tv_channels')->where('id', $masterChannelId)->value('name') ? $channel->name : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                if ($masterChannelId && (int) $masterChannelId !== (int) $channel->id) {
                    DB::table('tv_channels')->where('id', $channel->id)->update(['deleted_at' => now()]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_tv_channel');

        Schema::table('tv_channels', function (Blueprint $table) {
            $table->dropColumn(['tvg_id', 'group_title', 'source_type', 'source_logo_url', 'source_hash']);
        });

        DB::statement('ALTER TABLE tv_channels MODIFY stream_url VARCHAR(255) NULL');
    }
};
