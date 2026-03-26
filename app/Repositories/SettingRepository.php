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
