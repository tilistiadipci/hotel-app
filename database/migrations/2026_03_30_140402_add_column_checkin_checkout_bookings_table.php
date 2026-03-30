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
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('start_checkin')->nullable();
            $table->timestamp('end_checkin')->nullable();
            $table->timestamp('early_checkin')->nullable();
            $table->timestamp('early_checkout')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('start_checkin');
            $table->dropColumn('end_checkin');
            $table->dropColumn('early_checkin');
            $table->dropColumn('early_checkout');
        });
    }
};
