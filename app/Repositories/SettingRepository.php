<?php

namespace App\Repositories;

use App\Models\Setting;
use Exception;

class SettingRepository extends BaseRepository
{
    public function __construct(Setting $setting)
    {
        parent::__construct($setting);
    }

    public function getSettings()
    {
        $settings = [
            'default_language' => $this->getLanguageSetting(),
            'general_app_name' => $this->getValueByKey('general_app_name', config('app.name')),
            'general_app_logo' => $this->getValueByKey('general_app_logo', ''),
            'general_app_logo2' => $this->getValueByKey('general_app_logo2', ''),
            'menu_home_label' => $this->getValueByKey('menu_home_label', 'Home'),
            'menu_live_tv_label' => $this->getValueByKey('menu_live_tv_label', 'Live TV'),
            'menu_live_tv_status' => $this->getValueByKey('menu_live_tv_status', 'active'),
            'menu_streaming_tv_label' => $this->getValueByKey('menu_streaming_tv_label', 'Streaming TV'),
            'menu_streaming_tv_status' => $this->getValueByKey('menu_streaming_tv_status', 'active'),
            'menu_music_label' => $this->getValueByKey('menu_music_label', 'Music'),
            'menu_music_status' => $this->getValueByKey('menu_music_status', 'active'),
            'menu_vod_label' => $this->getValueByKey('menu_vod_label', 'VOD'),
            'menu_vod_status' => $this->getValueByKey('menu_vod_status', 'active'),
            'menu_guide_label' => $this->getValueByKey('menu_guide_label', 'Guide'),
            'menu_guide_status' => $this->getValueByKey('menu_guide_status', 'active'),
            'menu_nearby_label' => $this->getValueByKey('menu_nearby_label', 'Nearby'),
            'menu_nearby_status' => $this->getValueByKey('menu_nearby_status', 'active'),
            'menu_shopping_label' => $this->getValueByKey('menu_shopping_label', 'Shopping'),
            'menu_shopping_status' => $this->getValueByKey('menu_shopping_status', 'active'),
            'customize_menu_active' => $this->getValueByKey('customize_menu_active', 'inactive'),
            'other_apps_netflix' => $this->getValueByKey('other_apps_netflix', 'inactive'),
            'other_apps_vidio' => $this->getValueByKey('other_apps_vidio', 'inactive'),
            'other_apps_disney' => $this->getValueByKey('other_apps_disney', 'inactive'),
            'other_apps_wetv' => $this->getValueByKey('other_apps_wetv', 'inactive'),
            'other_apps_prime' => $this->getValueByKey('other_apps_prime', 'inactive'),
            'other_apps_youtube' => $this->getValueByKey('other_apps_youtube', 'inactive'),
            'mobile_menu_music' => $this->getValueByKey('mobile_menu_music', 'active'),
            'mobile_menu_vod' => $this->getValueByKey('mobile_menu_vod', 'active'),
            'mobile_menu_guide' => $this->getValueByKey('mobile_menu_guide', 'active'),
            'mobile_menu_nearby' => $this->getValueByKey('mobile_menu_nearby', 'active'),
            'mobile_menu_shopping' => $this->getValueByKey('mobile_menu_shopping', 'active'),
            'mobile_menu_other_page_website' => $this->getValueByKey(
                'mobile_menu_other_page_website',
                $this->getValueByKey('mobile_menu_other_apps', 'active')
            ),
            'about_phone' => $this->getValueByKey('about_phone', ''),
            'about_email' => $this->getValueByKey('about_email', ''),
            'about_website' => $this->getValueByKey('about_website', ''),
            'about_ssid' => $this->getValueByKey('about_ssid', ''),
            'about_wifi_password' => $this->getValueByKey('about_wifi_password', ''),
            'tax_percentage_grand_total_status' => $this->getValueByKey('tax_percentage_grand_total_status', 'inactive'),
            'tax_percentage_grand_total' => $this->getNumericValueByKey('tax_percentage_grand_total', 0),
            'service_charge_status' => $this->getValueByKey('service_charge_status', 'inactive'),
            'service_charge_fixed' => $this->getNumericValueByKey('service_charge_fixed', 0),
        ];

        session(['settings' => $settings]);

        return $settings;
    }

    public function getValueByKey(string $key, ?string $default = null): ?string
    {
        return $this->query()->where('key', $key)->value('value') ?? $default;
    }

    public function getBoolValueByKey(string $key, bool $default = false): bool
    {
        $value = $this->query()->where('key', $key)->value('value');

        if ($value === null) {
            return $default;
        }

        return in_array((string) $value, ['1', 'true', 'active', 'yes'], true);
    }

    public function getNumericValueByKey(string $key, int|float|string $default = 0): int|float|string
    {
        $value = $this->query()->where('key', $key)->value('value');

        return $value ?? $default;
    }

    public function saveByKey(string $name, string $key, ?string $value): Setting
    {
        $setting = $this->query()->firstOrNew(['key' => $key]);
        $setting->name = $name;
        $setting->value = $value;
        $setting->updated_by = auth()->id();

        if (!$setting->exists) {
            $setting->created_by = auth()->id();
        }

        $setting->save();

        return $setting;
    }

    protected function getLanguageSetting(): string
    {
        $langPath = base_path('settings/lang.json');

        if (!file_exists($langPath)) {
            return 'en_US';
        }

        try {
            $content = json_decode(file_get_contents($langPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            return 'en_US';
        }

        return ($content['lang_code'] ?? 'en') === 'id' ? 'id_ID' : 'en_US';
    }
}
