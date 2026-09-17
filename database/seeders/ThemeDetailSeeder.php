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
            'Executive Theme' => [
                'background_color' => '#0b0e14',
                'text_color' => '#f4efe4',
                'accent_color' => '#d4af37',
                'header_show_date' => '1',
                'header_show_title' => '1',
                'header_show_room_name' => '1',
                'running_text' => 'Welcome to Smartiv Hotel<br>We hope you have a memorable and enjoyable holiday<br>Get special promotions by following our Instagram @smartiv.tv',
                'notification_title' => 'Notification',
                'notification_message' => 'To the owner of the Porsche with license plate AB 1234 CC, please kindly move your vehicle parked in Basement 1, as it is blocking another car that needs to exit.<br><br>Thank you.',
                'wifi_ssid' => 'SmartivWiFi',
                'wifi_password' => 'yourpassword',
                // menu_N_icon keys start empty: each one holds a Media Library image id,
                // picked/uploaded by an admin via the theme editor's Menu tab.
                'menu_1_label' => 'Hotel Info',
                'menu_1_icon' => null,
                'menu_2_label' => 'TV',
                'menu_2_icon' => null,
                'menu_3_label' => 'Food Order',
                'menu_3_icon' => null,
                'menu_4_label' => 'Leisure',
                'menu_4_icon' => null,
                'menu_5_label' => 'CCTV',
                'menu_5_icon' => null,
                'menu_6_label' => 'Promo',
                'menu_6_icon' => null,
                'menu_7_label' => 'Chat',
                'menu_7_icon' => null,
            ],
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
