<?php

namespace Database\Seeders;

use App\Models\Theme;
use App\Models\ThemeDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ThemeDetailSeeder extends Seeder
{
    public function run(): void
    {
        $themes = [
            'Default Theme' => [
                'header_show_date' => '1',
                'header_show_title' => '1',
                'header_show_room_name' => '1',
                'background_theme_color' => 'dark-theme',
            ],
            'Executive Theme' => [],
        ];

        foreach ($themes as $themeName => $details) {
            $theme = Theme::query()->where('name', $themeName)->first();

            if (!$theme) {
                continue;
            }

            ThemeDetail::query()->where('theme_id', $theme->id)->delete();

            foreach ($details as $key => $value) {
                ThemeDetail::query()->updateOrCreate(
                    [
                        'theme_id' => $theme->id,
                        'key' => $key,
                    ],
                    [
                        'uuid' => Str::uuid()->toString(),
                        'value' => $value,
                    ]
                );
            }
        }
    }
}
