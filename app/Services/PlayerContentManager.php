<?php

namespace App\Services;

use App\Models\Player;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlayerContentManager
{
    /** @var array<string, array<string, mixed>> */
    private const MENUS = [
        'home' => ['name' => 'Home', 'label_key' => 'menu_home_label', 'status_key' => null, 'icon' => 'home', 'placement' => 'main', 'default_active' => true],
        'live_tv' => ['name' => 'Live TV', 'label_key' => 'menu_live_tv_label', 'status_key' => 'menu_live_tv_status', 'icon' => 'tv', 'placement' => 'main', 'default_active' => true],
        'streaming_tv' => ['name' => 'Streaming TV', 'label_key' => 'menu_streaming_tv_label', 'status_key' => 'menu_streaming_tv_status', 'icon' => 'streaming', 'placement' => 'main', 'default_active' => true],
        'music' => ['name' => 'Music / Songs', 'label_key' => 'menu_music_label', 'status_key' => 'menu_music_status', 'icon' => 'music', 'placement' => 'main', 'default_active' => true],
        'vod' => ['name' => 'Movies / VOD', 'label_key' => 'menu_vod_label', 'status_key' => 'menu_vod_status', 'icon' => 'movie', 'placement' => 'main', 'default_active' => true],
        'guide' => ['name' => 'Guide', 'label_key' => 'menu_guide_label', 'status_key' => 'menu_guide_status', 'icon' => 'guide', 'placement' => 'main', 'default_active' => true],
        'nearby' => ['name' => 'Nearby / Places', 'label_key' => 'menu_nearby_label', 'status_key' => 'menu_nearby_status', 'icon' => 'place', 'placement' => 'main', 'default_active' => true],
        'shopping' => ['name' => 'Shopping', 'label_key' => 'menu_shopping_label', 'status_key' => 'menu_shopping_status', 'icon' => 'shopping', 'placement' => 'main', 'default_active' => true],
        'netflix' => ['name' => 'Netflix', 'label_key' => null, 'status_key' => 'other_apps_netflix', 'icon' => 'netflix', 'placement' => 'submenu', 'parent' => 'streaming_tv', 'default_active' => false, 'requires_apps' => true],
        'vidio' => ['name' => 'Vidio', 'label_key' => null, 'status_key' => 'other_apps_vidio', 'icon' => 'vidio', 'placement' => 'submenu', 'parent' => 'streaming_tv', 'default_active' => false, 'requires_apps' => true],
        'disney' => ['name' => 'Disney+', 'label_key' => null, 'status_key' => 'other_apps_disney', 'icon' => 'disney', 'placement' => 'submenu', 'parent' => 'streaming_tv', 'default_active' => false, 'requires_apps' => true],
        'wetv' => ['name' => 'WeTV', 'label_key' => null, 'status_key' => 'other_apps_wetv', 'icon' => 'wetv', 'placement' => 'submenu', 'parent' => 'streaming_tv', 'default_active' => false, 'requires_apps' => true],
        'prime' => ['name' => 'Prime Video', 'label_key' => null, 'status_key' => 'other_apps_prime', 'icon' => 'prime', 'placement' => 'submenu', 'parent' => 'streaming_tv', 'default_active' => false, 'requires_apps' => true],
        'youtube' => ['name' => 'YouTube', 'label_key' => null, 'status_key' => 'other_apps_youtube', 'icon' => 'youtube', 'placement' => 'submenu', 'parent' => 'streaming_tv', 'default_active' => false, 'requires_apps' => true],
    ];

    /** @return array<int, string> */
    public function keys(): array
    {
        return array_keys(self::MENUS);
    }

    /** @return array<string, string> */
    public function iconOptions(): array
    {
        return [
            'home' => 'Home', 'tv' => 'TV', 'streaming' => 'Streaming', 'music' => 'Music',
            'movie' => 'Movie', 'guide' => 'Guide', 'place' => 'Place', 'shopping' => 'Shopping',
            'apps' => 'Apps', 'netflix' => 'Netflix', 'vidio' => 'Vidio', 'disney' => 'Disney+',
            'wetv' => 'WeTV', 'prime' => 'Prime Video', 'youtube' => 'YouTube',
        ];
    }

    public function effective(Player $player): Collection
    {
        $global = $this->global($player);

        if (! $player->use_custom_content) {
            return $this->withParentDetails($global);
        }

        return $this->withParentDetails($this->applyOverrides($player, $global));
    }

    public function editable(Player $player): Collection
    {
        return $this->withParentDetails($this->applyOverrides($player, $this->global($player)));
    }

    private function withParentDetails(Collection $menus): Collection
    {
        $menusByKey = $menus->keyBy('key');

        return $menus->map(function (array $menu) use ($menusByKey): array {
            $parent = $menu['parent_menu_key']
                ? $menusByKey->get($menu['parent_menu_key'])
                : null;

            $menu['parent_menu'] = $parent
                ? ['key' => $parent['key'], 'label' => $parent['label']]
                : null;

            return $menu;
        });
    }

    private function applyOverrides(Player $player, Collection $global): Collection
    {
        $overrides = $player->menuSettings()->get()->keyBy('menu_key');

        return $global->map(function (array $menu) use ($overrides): array {
            $override = $overrides->get($menu['key']);

            if (! $override) {
                return $menu;
            }

            return array_merge($menu, [
                'label' => $override->label,
                'icon' => $override->icon ?: $menu['icon'],
                'icon_path' => $override->icon_path,
                'icon_url' => $override->icon_path ? getMediaImageUrl($override->icon_path, 256, 256) : null,
                'placement' => $override->placement,
                'parent_menu_key' => $override->placement === 'submenu'
                    ? ($override->parent_menu_key ?: $menu['parent_menu_key'])
                    : null,
                'is_active' => (bool) $override->is_active,
                'sort_order' => (int) $override->sort_order,
                'source' => 'player',
            ]);
        })->sortBy('sort_order')->values();
    }

    /** @param array<int, array<string, mixed>> $menus */
    public function save(Player $player, bool $useCustom, array $menus, ?int $themeId = null): array
    {
        return DB::transaction(function () use ($player, $useCustom, $menus, $themeId): array {
            $obsoleteIconPaths = [];
            $player->use_custom_content = $useCustom;
            if ($themeId !== null) {
                $player->theme_id = $themeId;
            }
            $player->updated_by = auth()->id();
            $player->save();

            if (! $useCustom) {
                return [];
            }

            $submittedKeys = collect($menus)->pluck('key')->all();
            $removedSettings = $player->menuSettings()->whereNotIn('menu_key', $submittedKeys ?: ['__none__'])->get();
            $obsoleteIconPaths = array_merge($obsoleteIconPaths, $removedSettings->pluck('icon_path')->filter()->all());
            $player->menuSettings()->whereKey($removedSettings->pluck('id'))->delete();

            foreach ($menus as $index => $menu) {
                $existing = $player->menuSettings()->where('menu_key', $menu['key'])->first();
                $iconPath = $existing?->icon_path;

                if (! empty($menu['_uploaded_icon_path'])) {
                    if ($iconPath && $iconPath !== $menu['_uploaded_icon_path']) {
                        $obsoleteIconPaths[] = $iconPath;
                    }
                    $iconPath = $menu['_uploaded_icon_path'];
                } elseif (! empty($menu['remove_icon'])) {
                    if ($iconPath) {
                        $obsoleteIconPaths[] = $iconPath;
                    }
                    $iconPath = null;
                }

                $player->menuSettings()->updateOrCreate(
                    ['menu_key' => $menu['key']],
                    [
                        'label' => trim((string) $menu['label']),
                        'icon' => trim((string) ($menu['icon'] ?? '')) ?: null,
                        'icon_path' => $iconPath,
                        'placement' => $menu['placement'],
                        'parent_menu_key' => $menu['placement'] === 'submenu'
                            ? ($menu['parent_menu_key'] ?? null)
                            : null,
                        'is_active' => (bool) $menu['is_active'],
                        'sort_order' => (int) ($menu['sort_order'] ?? $index),
                    ]
                );
            }

            return array_values(array_unique($obsoleteIconPaths));
        });
    }

    private function global(Player $player): Collection
    {
        $settings = Setting::query()
            ->forHotel((string) $player->hotel_id)
            ->pluck('value', 'key');

        return collect(self::MENUS)->map(function (array $definition, string $key) use ($settings): array {
            $label = $definition['label_key']
                ? $settings->get($definition['label_key'], $definition['name'])
                : $definition['name'];
            $active = $definition['status_key'] === null
                || $settings->get($definition['status_key'], $definition['default_active'] ? 'active' : 'inactive') === 'active';

            if (($definition['requires_apps'] ?? false) && $settings->get('customize_menu_active', 'inactive') !== 'active') {
                $active = false;
            }

            return [
                'key' => $key,
                'name' => $definition['name'],
                'label' => (string) $label,
                'icon' => $definition['icon'],
                'icon_path' => null,
                'icon_url' => null,
                'placement' => $definition['placement'],
                'parent_menu_key' => $definition['placement'] === 'submenu'
                    ? ($definition['parent'] ?? null)
                    : null,
                'is_active' => $active,
                'sort_order' => array_search($key, array_keys(self::MENUS), true),
                'source' => 'global',
            ];
        })->values();
    }
}
