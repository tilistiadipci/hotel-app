<?php

namespace App\Repositories;

use App\Models\Setting;

class SettingRepository extends BaseRepository
{
    public function __construct(Setting $setting)
    {
        parent::__construct($setting);
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
}
