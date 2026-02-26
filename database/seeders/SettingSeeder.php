<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SettingSeeder extends Seeder
{
    // R0pHRk5iVVRUTDhvN0lPaWR2am9MbVJ6d0dWMStRSXBJQW9JNnBGeFhkMXdkUUUveituVlVRQjVZb0JYdzRhbg==
    public function run(): void
    {
        $now = Carbon::now();

        $settings = [
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'API Key Status',
                'key'        => 'api_key_status',
                'value'      => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'API Key',
                'key'        => 'api_key_value',
                'value'      => secureEncrypt("y0zr3hl33boako2pm90fyajmzmqubcec"),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('settings')->upsert(
            $settings,
            ['key'],
            [
                'name',
                'value',
                'updated_at',
            ]
        );
    }
}
