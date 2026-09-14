<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (DB::table('hotels')->whereNull('deleted_at')->pluck('id') as $hotelId) {
            if (DB::table('settings')->where('hotel_id', $hotelId)->where('key', 'default_language')->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'uuid' => (string) Str::uuid(),
                'hotel_id' => $hotelId,
                'name' => 'Default Language',
                'key' => 'default_language',
                'value' => 'id_ID',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'default_language')->delete();
    }
};
