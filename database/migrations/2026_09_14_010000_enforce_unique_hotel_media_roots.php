<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasUniqueIndex = collect(Schema::getIndexes('hotel_configurations'))
            ->contains(fn (array $index) => ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['media_root']);

        if ($hasUniqueIndex) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE hotel_configurations MODIFY media_root VARCHAR(191) NOT NULL');
        }

        Schema::table('hotel_configurations', function (Blueprint $table) {
            $table->unique('media_root');
        });
    }

    public function down(): void
    {
        $hasUniqueIndex = collect(Schema::getIndexes('hotel_configurations'))
            ->contains(fn (array $index) => ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['media_root']);

        if ($hasUniqueIndex) {
            Schema::table('hotel_configurations', function (Blueprint $table) {
                $table->dropUnique(['media_root']);
            });
        }
    }
};
