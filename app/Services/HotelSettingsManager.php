<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Media;
use App\Models\Setting;
use App\Models\Theme;
use Illuminate\Support\Facades\DB;

class HotelSettingsManager
{
    public function values(Hotel $hotel): array
    {
        $stored = Setting::query()->forHotel($hotel->id)->pluck('value', 'key')->all();

        return $this->settingDefinitions()
            ->mapWithKeys(fn (array $field, string $key) => [$key => $stored[$key] ?? $field['default']])
            ->all();
    }

    public function viewData(Hotel $hotel): array
    {
        return [
            'hotelSettings' => $this->values($hotel),
            'hotelSettingGroups' => $this->definitions(),
            'hotelImageMedia' => Media::query()->forHotel($hotel->id)
                ->where('type', 'image')
                ->orderBy('name')
                ->get(['id', 'name', 'original_filename']),
            'hotelThemes' => Theme::query()->forHotel($hotel->id)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(['id', 'name', 'is_default']),
            'hotelDefaultThemeId' => Theme::query()->forHotel($hotel->id)
                ->where('is_default', '1')
                ->value('id'),
        ];
    }

    public function save(Hotel $hotel, array $values, ?int $themeId, ?int $userId): void
    {
        $definitions = $this->settingDefinitions();

        DB::transaction(function () use ($hotel, $values, $themeId, $userId, $definitions): void {
            foreach ($definitions as $key => $definition) {
                $setting = Setting::query()->forHotel($hotel->id)->withTrashed()->firstOrNew(['key' => $key]);
                $setting->hotel_id = $hotel->id;
                $setting->name = $definition['name'];
                $setting->value = array_key_exists($key, $values) ? $values[$key] : $definition['default'];
                $setting->updated_by = $userId;
                $setting->deleted_at = null;

                if (! $setting->exists) {
                    $setting->created_by = $userId;
                }

                $setting->save();
            }

            Theme::query()->forHotel($hotel->id)->update([
                'is_default' => '0',
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);

            if ($themeId !== null) {
                Theme::query()->forHotel($hotel->id)->whereKey($themeId)->update([
                    'is_default' => '1',
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function definitions(): array
    {
        $status = ['active' => trans('common.active'), 'inactive' => trans('common.inactive')];

        return [
            'general' => [
                'title' => trans('platform.hotel_settings.groups.general'),
                'icon' => 'fa fa-cog',
                'fields' => [
                    'default_language' => $this->field('Default Language', trans('platform.hotel_settings.fields.default_language'), 'select', 'id_ID', [
                        'id_ID' => trans('common.settings_page.bahasa_indonesia'),
                        'en_US' => trans('common.settings_page.english_us'),
                    ]),
                    'general_app_name' => $this->field('General App Name', trans('platform.hotel_settings.fields.app_name'), 'text', '', null, 150),
                    'api_key_status' => $this->field('API Key Status', trans('common.settings_page.api_key_status'), 'select', 'active', $status),
                    'longitude_app' => $this->field('Longitude', 'Longitude', 'number', '0'),
                    'latitude_app' => $this->field('Latitude', 'Latitude', 'number', '0'),
                ],
            ],
            'menus' => [
                'title' => trans('platform.hotel_settings.groups.menus'),
                'icon' => 'fa fa-bars',
                'fields' => [
                    'menu_home_label' => $this->field('Menu Home Label', trans('common.settings_page.menu_home'), 'text', 'Home', null, 80),
                    'menu_live_tv_label' => $this->field('Menu Live TV Label', trans('common.settings_page.menu_live_tv'), 'text', 'Live TV', null, 80),
                    'menu_live_tv_status' => $this->field('Menu Live TV Status', trans('platform.hotel_settings.fields.live_tv_status'), 'select', 'active', $status),
                    'menu_streaming_tv_label' => $this->field('Menu Streaming TV Label', trans('common.settings_page.menu_streaming_tv'), 'text', 'Streaming TV', null, 80),
                    'menu_streaming_tv_status' => $this->field('Menu Streaming TV Status', trans('platform.hotel_settings.fields.streaming_tv_status'), 'select', 'active', $status),
                    'menu_music_label' => $this->field('Menu Music Label', trans('common.settings_page.menu_music'), 'text', 'Music', null, 80),
                    'menu_music_status' => $this->field('Menu Music Status', trans('platform.hotel_settings.fields.music_status'), 'select', 'active', $status),
                    'menu_vod_label' => $this->field('Menu VOD Label', trans('common.settings_page.menu_vod'), 'text', 'VOD', null, 80),
                    'menu_vod_status' => $this->field('Menu VOD Status', trans('platform.hotel_settings.fields.vod_status'), 'select', 'active', $status),
                    'menu_guide_label' => $this->field('Menu Guide Label', trans('common.settings_page.menu_guide'), 'text', 'Guide', null, 80),
                    'menu_guide_status' => $this->field('Menu Guide Status', trans('platform.hotel_settings.fields.guide_status'), 'select', 'active', $status),
                    'menu_nearby_label' => $this->field('Menu Nearby Label', trans('common.settings_page.menu_nearby'), 'text', 'Nearby', null, 80),
                    'menu_nearby_status' => $this->field('Menu Nearby Status', trans('platform.hotel_settings.fields.nearby_status'), 'select', 'active', $status),
                    'menu_shopping_label' => $this->field('Menu Shopping Label', trans('common.settings_page.menu_shopping'), 'text', 'Shopping', null, 80),
                    'menu_shopping_status' => $this->field('Menu Shopping Status', trans('platform.hotel_settings.fields.shopping_status'), 'select', 'active', $status),
                ],
            ],
            'apps' => [
                'title' => trans('platform.hotel_settings.groups.apps'),
                'icon' => 'fa fa-th-large',
                'fields' => [
                    'customize_menu_active' => $this->field('Customize Menu Other Active', trans('common.settings_page.customize_menu_other'), 'select', 'inactive', $status),
                    'other_apps_netflix' => $this->field('Other Apps Netflix', 'Netflix', 'select', 'inactive', $status),
                    'other_apps_vidio' => $this->field('Other Apps Vidio', 'Vidio', 'select', 'inactive', $status),
                    'other_apps_disney' => $this->field('Other Apps Disney', 'Disney+', 'select', 'inactive', $status),
                    'other_apps_wetv' => $this->field('Other Apps WeTV', 'WeTV', 'select', 'inactive', $status),
                    'other_apps_prime' => $this->field('Other Apps Prime', 'Prime Video', 'select', 'inactive', $status),
                    'other_apps_youtube' => $this->field('Other Apps YouTube', 'YouTube', 'select', 'inactive', $status),
                ],
            ],
            'mobile' => [
                'title' => trans('platform.hotel_settings.groups.mobile'),
                'icon' => 'fa fa-mobile',
                'fields' => [
                    'mobile_menu_music' => $this->field('Mobile Menu Music', trans('common.settings_page.menu_music'), 'select', 'active', $status),
                    'mobile_menu_vod' => $this->field('Mobile Menu VOD', trans('common.settings_page.menu_vod'), 'select', 'active', $status),
                    'mobile_menu_guide' => $this->field('Mobile Menu Guide', trans('common.settings_page.menu_guide'), 'select', 'active', $status),
                    'mobile_menu_nearby' => $this->field('Mobile Menu Nearby', trans('common.settings_page.menu_nearby'), 'select', 'active', $status),
                    'mobile_menu_shopping' => $this->field('Mobile Menu Shopping', trans('common.settings_page.menu_shopping'), 'select', 'active', $status),
                    'mobile_menu_other_page_website' => $this->field('Mobile Menu Other Page Website', trans('common.settings_page.other_page_website'), 'select', 'active', $status),
                ],
            ],
            'transaction' => [
                'title' => trans('platform.hotel_settings.groups.transaction'),
                'icon' => 'fa fa-calculator',
                'fields' => [
                    'tax_percentage_grand_total_status' => $this->field('Tax Percentage Grand Total Status', trans('common.settings_page.tax_percentage_grand_total_status'), 'select', 'inactive', $status),
                    'tax_percentage_grand_total' => $this->field('Tax Percentage Grand Total (%)', trans('common.settings_page.tax_percentage_grand_total'), 'number', '0'),
                    'service_charge_status' => $this->field('Service Charge Status', trans('common.settings_page.service_charge_status'), 'select', 'inactive', $status),
                    'service_charge_fixed' => $this->field('Service Charge (Fixed)', trans('common.settings_page.service_charge_fixed'), 'number', '0'),
                    'alert_notification' => $this->field('Alert Notification', trans('common.settings_page.alert_notification'), 'select', 'inactive', $status),
                    'warning_broadcast_status' => $this->field('Warning Broadcast Status', trans('common.settings_page.warning_broadcast'), 'select', 'active', $status),
                ],
            ],
            'contact' => [
                'title' => trans('platform.hotel_settings.groups.contact'),
                'icon' => 'fa fa-address-card',
                'fields' => [
                    'about_phone' => $this->field('About Phone Number', trans('common.settings_page.about_phone'), 'text', '', null, 50),
                    'about_email' => $this->field('About Email', trans('common.settings_page.about_email'), 'email', '', null, 150),
                    'about_website' => $this->field('About Website', trans('common.settings_page.about_website'), 'url', '', null, 255),
                    'about_ssid' => $this->field('About SSID', trans('common.settings_page.about_ssid'), 'text', '', null, 100),
                    'about_wifi_password' => $this->field('About WIFI Password', trans('common.settings_page.about_wifi_password'), 'text', '', null, 150),
                ],
            ],
        ];
    }

    private function field(string $name, string $label, string $type, string $default, ?array $options = null, ?int $max = null): array
    {
        return compact('name', 'label', 'type', 'default', 'options', 'max');
    }

    private function settingDefinitions()
    {
        return collect($this->definitions())
            ->flatMap(fn (array $group) => $group['fields'])
            ->merge([
                'general_app_logo' => $this->field('General App Logo', trans('platform.hotel_settings.fields.logo_primary'), 'media', ''),
                'general_app_logo2' => $this->field('General App Logo 2', trans('platform.hotel_settings.fields.logo_secondary'), 'media', ''),
            ]);
    }
}
