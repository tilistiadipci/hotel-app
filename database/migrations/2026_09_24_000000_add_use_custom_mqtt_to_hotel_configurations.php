<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_configurations', function (Blueprint $table) {
            $table->boolean('use_custom_mqtt')->default(false)->after('media_root');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_configurations', function (Blueprint $table) {
            $table->dropColumn('use_custom_mqtt');
        });
    }
};
