<?php

namespace App\Services;

use App\Models\Player;
use App\Repositories\SettingRepository;
use Illuminate\Support\Facades\Schema;

class PlayerOtherSettingsManager
{
    public const KEYS = [
        'about_phone',
        'about_email',
        'about_website',
        'about_ssid',
        'about_wifi_password',
    ];

    public function __construct(private readonly SettingRepository $settings)
    {
    }

    public function effective(Player $player): array
    {
        $global = $this->globalValues();

        if (! Schema::hasTable('player_setting_overrides')) {
            return [
                'uses_override' => false,
                'values' => $global,
            ];
        }

        $overrides = $player->settingOverrides()
            ->whereIn('setting_key', self::KEYS)
            ->pluck('value', 'setting_key')
            ->all();

        return [
            'uses_override' => ! empty($overrides),
            'values' => array_merge($global, $overrides),
        ];
    }

    public function save(Player $player, bool $useOverride, array $values): void
    {
        $player->settingOverrides()->whereIn('setting_key', self::KEYS)->delete();

        if (! $useOverride) {
            return;
        }

        foreach (self::KEYS as $key) {
            $player->settingOverrides()->create([
                'setting_key' => $key,
                'value' => $values[$key] ?? null,
            ]);
        }
    }

    public function globalValues(): array
    {
        $settings = $this->settings->getSettings();

        return collect(self::KEYS)
            ->mapWithKeys(fn (string $key): array => [$key => $settings[$key] ?? ''])
            ->all();
    }
}
