<?php

namespace Database\Seeders;

use App\Models\Theme;
use App\Models\ThemeDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ThemeDetailSeeder extends Seeder
{
    public function run(): void
    {
        $hotelId = DB::table('hotels')
            ->where('is_system', false)
            ->whereNull('deleted_at')
            ->orderBy('created_at')
            ->value('id');

        if (! $hotelId) {
            return;
        }

        $themes = [
            'Default Theme' => [
                'header_show_date' => '1',
                'header_show_title' => '1',
                'header_show_room_name' => '1',
                'header_scale' => '1',
                'footer_scale' => '1',
                'font_scale' => '1',
                'image_id_1' => '1',
                'image_id_2' => '2',
                'image_id_3' => '1',
            ],
            'Executive Theme' => [],
        ];

        foreach ($themes as $themeName => $details) {
            $theme = Theme::query()->where('name', $themeName)->first();

            if (! $theme) {
                continue;
            }

            foreach ($details as $key => $value) {
                ThemeDetail::query()->firstOrCreate(
                    [
                        'hotel_id' => $hotelId,
                        'theme_id' => $theme->id,
                        'key' => $key,
                    ],
                    [
                        'uuid' => (string) Str::uuid(),
                        'value' => $value,
                    ]
                );
            }
        }
    }
}
