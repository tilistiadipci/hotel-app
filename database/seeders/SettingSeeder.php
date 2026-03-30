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
                'name'       => 'General App Name',
                'key'        => 'general_app_name',
                'value'      => 'My Hotel',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'General App Logo',
                'key'        => 'general_app_logo',
                'value'      => 1, // media_id
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'General App Logo',
                'key'        => 'general_app_logo2',
                'value'      => 1, // media_id
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Tax Percentage Grand Total Status',
                'key'        => 'tax_percentage_grand_total_status',
                'value'      => 'inactive', // active or inactive
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Tax Percentage Grand Total (%)',
                'key'        => 'tax_percentage_grand_total',
                'value'      => 12,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Service Charge Status',
                'key'        => 'service_charge_status',
                'value'      => 'inactive', // active or inactive
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Service Charge (Fixed)',
                'key'        => 'service_charge_fixed',
                'value'      => 10000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'About Phone Number',
                'key'        => 'about_phone',
                'value'      => '081234567890',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'About Email',
                'key'        => 'about_email',
                'value'      => 'info@myhotel.com',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'About Website',
                'key'        => 'about_website',
                'value'      => 'https://www.myhotel.com',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'About SSID',
                'key'        => 'about_ssid',
                'value'      => 'MyHotel-WiFi',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'About WiFi Password',
                'key'        => 'about_wifi_password',
                'value'      => 'MyHotel123',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // menu
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Menu Home Label',
                'key'        => 'menu_home_label',
                'value'      => 'Home',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Menu Live TV Label',
                'key'        => 'menu_live_tv_label',
                'value'      => 'Live TV',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Menu Live TV Status',
                'key'        => 'menu_live_tv_status',
                'value'      => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Menu Streaming TV Label',
                'key'        => 'menu_streaming_tv_label',
                'value'      => 'Streaming TV',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Menu Streaming TV Status',
                'key'        => 'menu_streaming_tv_status',
                'value'      => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Menu Music Label',
                'key'        => 'menu_music_label',
                'value'      => 'Music',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Menu Music Status',
                'key'        => 'menu_music_status',
                'value'      => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Menu VOD Label',
                'key'        => 'menu_vod_label',
                'value'      => 'VOD',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Menu VOD Status',
                'key'        => 'menu_vod_status',
                'value'      => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Menu Guide Label',
                'key'        => 'menu_guide_label',
                'value'      => 'Guide',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Menu Guide Status',
                'key'        => 'menu_guide_status',
                'value'      => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Menu Nearby Label',
                'key'        => 'menu_nearby_label',
                'value'      => 'Nearby',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Menu Nearby Status',
                'key'        => 'menu_nearby_status',
                'value'      => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Menu Shopping Label',
                'key'        => 'menu_shopping_label',
                'value'      => 'Shopping',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Menu Shopping Status',
                'key'        => 'menu_shopping_status',
                'value'      => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Customize Menu Other Active',
                'key'        => 'customize_menu_other_active',
                'value'      => 'inactive',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Other Apps Netflix',
                'key'        => 'other_apps_netflix',
                'value'      => 'inactive',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Other Apps Vidio',
                'key'        => 'other_apps_vidio',
                'value'      => 'inactive',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Other Apps Disney',
                'key'        => 'other_apps_disney',
                'value'      => 'inactive',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Other Apps WeTV',
                'key'        => 'other_apps_wetv',
                'value'      => 'inactive',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Other Apps Prime',
                'key'        => 'other_apps_prime',
                'value'      => 'inactive',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Other Apps YouTube',
                'key'        => 'other_apps_youtube',
                'value'      => 'inactive',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Mobile Menu Music',
                'key'        => 'mobile_menu_music',
                'value'      => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Mobile Menu VOD',
                'key'        => 'mobile_menu_vod',
                'value'      => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Mobile Menu Guide',
                'key'        => 'mobile_menu_guide',
                'value'      => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Mobile Menu Nearby',
                'key'        => 'mobile_menu_nearby',
                'value'      => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Mobile Menu Shopping',
                'key'        => 'mobile_menu_shopping',
                'value'      => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Mobile Menu Other Page Website',
                'key'        => 'mobile_menu_other_page_website',
                'value'      => 'active',
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
