<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_tv_channel', function (Blueprint $table) {
            $table->string('custom_type', 20)->nullable()->after('custom_name');
            $table->string('custom_region', 20)->nullable()->after('custom_type');
            $table->text('custom_stream_url')->nullable()->after('custom_region');
            $table->string('custom_frequency', 60)->nullable()->after('custom_stream_url');
            $table->string('custom_quality', 20)->nullable()->after('custom_frequency');
            $table->unsignedBigInteger('custom_image_id')->nullable()->after('custom_quality');
            $table->foreign('custom_image_id')->references('id')->on('medias')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hotel_tv_channel', function (Blueprint $table) {
            $table->dropForeign(['custom_image_id']);
            $table->dropColumn([
                'custom_type',
                'custom_region',
                'custom_stream_url',
                'custom_frequency',
                'custom_quality',
                'custom_image_id',
            ]);
        });
    }
};
