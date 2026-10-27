<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $settings = [
            ['menu_mobile_label', 'Menu Mobile Label', 'text', 'Mobile', 80],
            ['menu_mobile_status', 'Menu Mobile Status', 'select', 'active', null],
        ];

        foreach ($settings as [$key, $name, $type, $value, $maxLength]) {
            if (Schema::hasTable('master_settings') && ! DB::table('master_settings')->where('key', $key)->exists()) {
                DB::table('master_settings')->insert([
                    'uuid' => (string) Str::uuid(),
                    'group_key' => 'menus',
                    'key' => $key,
                    'name' => $name,
                    'type' => $type,
                    'value' => $value,
                    'options' => $type === 'select' ? json_encode(['active' => 'Active', 'inactive' => 'Inactive']) : null,
                    'max_length' => $maxLength,
                    'sort_order' => (int) DB::table('master_settings')->max('sort_order') + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach (DB::table('hotels')->whereNull('deleted_at')->pluck('id') as $hotelId) {
                if (DB::table('settings')->where('hotel_id', $hotelId)->where('key', $key)->exists()) {
                    continue;
                }

                DB::table('settings')->insert([
                    'uuid' => (string) Str::uuid(),
                    'hotel_id' => $hotelId,
                    'key' => $key,
                    'name' => $name,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['menu_mobile_label', 'menu_mobile_status'])->delete();

        if (Schema::hasTable('master_settings')) {
            DB::table('master_settings')->whereIn('key', ['menu_mobile_label', 'menu_mobile_status'])->delete();
        }
    }
};
