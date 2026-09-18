<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Repositories\PlayerMqttRepository;
use App\Repositories\PlayerRepository;
use App\Repositories\ThemeRepository;
use App\Services\PlayerContentManager;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlayerContentController extends Controller
{
    public function __construct(
        private readonly PlayerRepository $players,
        private readonly ThemeRepository $themes,
        private readonly PlayerContentManager $content,
        private readonly PlayerMqttRepository $mqtt,
    ) {
    }

    public function edit(string $player)
    {
        $player = $this->players->findUid($player);
        abort_unless($player, 404);

        $themes = $this->themes->getList();
        $defaultTheme = $themes->first(
            fn ($theme) => (string) ($theme->is_default ?? '0') === '1'
        ) ?? $themes->first();
        $selectedThemeId = old('theme_id', $player->theme_id ?: $defaultTheme?->id);
        $themeOptions = $themes->map(function ($theme): array {
            return [
                'id' => $theme->id,
                'name' => $theme->name,
                'description' => $theme->description,
                'is_default' => (string) ($theme->is_default ?? '0') === '1',
                'image_url' => $theme->imageMedia
                    ? getMediaImageUrl($theme->imageMedia->storage_path, 480, 270)
                    : asset('images/theme_'.$theme->id.'.png'),
                'details' => $theme->details->pluck('value', 'key')->all(),
            ];
        });

        $menus = $this->content->editable($player);
        if (is_array(old('menus'))) {
            $storedMenus = $menus->keyBy('key');
            $menus = collect(old('menus'))->map(function (array $menu, int|string $index) use ($storedMenus): array {
                $key = (string) ($menu['key'] ?? '');
                $stored = $storedMenus->get($key, []);

                return array_merge([
                    'key' => $key,
                    'name' => $menu['label'] ?? $key,
                    'label' => $menu['label'] ?? '',
                    'icon' => $menu['icon'] ?? 'apps',
                    'icon_path' => null,
                    'icon_url' => null,
                    'placement' => $menu['placement'] ?? 'main',
                    'parent_menu_key' => $menu['parent_menu_key'] ?? null,
                    'is_active' => (bool) ($menu['is_active'] ?? false),
                    'sort_order' => $menu['sort_order'] ?? $index,
                    'source' => 'player',
                    'is_custom' => ! $this->content->isBuiltIn($key),
                ], $stored, $menu, [
                    'icon_path' => $stored['icon_path'] ?? null,
                    'icon_url' => $stored['icon_url'] ?? null,
                    'is_custom' => ! $this->content->isBuiltIn($key),
                ]);
            })->values();
        }

        return view('pages.players.content', [
            'page' => 'players',
            'icon' => 'fa fa-th-large',
            'player' => $player,
            'menus' => $menus,
            'iconOptions' => $this->content->iconOptions(),
            'themeOptions' => $themeOptions,
            'selectedThemeId' => $selectedThemeId,
        ]);
    }

    public function update(Request $request, string $player)
    {
        $player = $this->players->findUid($player);
        abort_unless($player, 404);

        $request->merge(['use_custom_content' => $request->boolean('use_custom_content')]);
        $validated = $request->validate([
            'use_custom_content' => ['required', 'boolean'],
            'theme_id' => [
                'nullable',
                'integer',
                Rule::exists('hotel_theme', 'theme_id')->where(
                    fn ($query) => $query->where('hotel_id', app(TenantContext::class)->id())
                ),
            ],
            'menus' => ['nullable', 'array', 'max:100'],
            'menus.*.key' => ['required', 'string', 'distinct', 'max:50', 'regex:/^[a-z][a-z0-9_-]*$/'],
            'menus.*.label' => ['required', 'string', 'max:100'],
            'menus.*.icon' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 _-]+$/'],
            'menus.*.icon_media_id' => ['nullable', 'integer', 'min:1'],
            'menus.*.remove_icon' => ['nullable', 'boolean'],
            'menus.*.placement' => ['required', Rule::in(['main', 'submenu'])],
            'menus.*.parent_menu_key' => ['nullable', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_-]*$/'],
            'menus.*.is_active' => ['required', 'boolean'],
            'menus.*.sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $menus = $validated['menus'] ?? [];
        $placements = collect($menus)->pluck('placement', 'key');

        foreach ($menus as $index => $menu) {
            if ($menu['placement'] !== 'submenu') {
                continue;
            }

            $parent = $menu['parent_menu_key'] ?? null;
            if (! $parent || $parent === $menu['key'] || $placements->get($parent) !== 'main') {
                throw ValidationException::withMessages([
                    "menus.$index.parent_menu_key" => trans('common.player_content.parent_validation'),
                ]);
            }
        }

        $skippedIconUploads = [];
        foreach ($menus as $index => &$menu) {
            $mediaId = $menu['icon_media_id'] ?? null;
            if (empty($mediaId)) {
                continue;
            }

            $media = Media::query()->where('type', 'image')->find((int) $mediaId);
            if (! $media) {
                $skippedIconUploads[] = $menu['label'];
                continue;
            }

            $menu['_uploaded_icon_path'] = $media->storage_path;
        }
        unset($menu);

        $themeId = isset($validated['theme_id'])
            ? (int) $validated['theme_id']
            : ($player->theme_id ?: $this->themes->getList()->first(
                fn ($theme) => (string) ($theme->is_default ?? '0') === '1'
            )?->id);
        $this->content->save(
            $player,
            (bool) $validated['use_custom_content'],
            $menus,
            $themeId ? (int) $themeId : null
        );

        try {
            $this->mqtt->publishPlayerUpdate($player->fresh(), 'menus');
        } catch (\Throwable $exception) {
            report($exception);
        }

        $redirect = redirect()
            ->route('players.content.edit', $player->uuid)
            ->with('success', trans('common.player_content.success'));

        if ($skippedIconUploads) {
            $redirect->with('warning', trans('common.player_content.icon_upload_skipped', [
                'menus' => implode(', ', $skippedIconUploads),
            ]));
        }

        return $redirect;
    }
}
