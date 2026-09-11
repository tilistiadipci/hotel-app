<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_licenses', function (Blueprint $table) {
            $table->char('license_key_fingerprint', 64)->nullable()->unique()->after('license_key_hash');
        });

        DB::table('hotel_licenses')
            ->whereNotIn('plan_code', ['trial', 'standard', 'premium', 'custom'])
            ->update(['plan_code' => 'custom']);
    }

    public function down(): void
    {
        Schema::table('hotel_licenses', function (Blueprint $table) {
            $table->dropUnique(['license_key_fingerprint']);
            $table->dropColumn('license_key_fingerprint');
        });
    }
};
