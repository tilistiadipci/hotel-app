<?php

namespace App\Http\Controllers;

use App\Repositories\PlayerMqttRepository;
use App\Repositories\PlayerRepository;
use App\Repositories\ThemeRepository;
use App\Services\PlayerContentManager;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
                    : getMediaImageUrl('default/theme-'.$theme->id.'.png', 480, 270),
            ];
        });

        return view('pages.players.content', [
            'page' => 'players',
            'icon' => 'fa fa-th-large',
            'player' => $player,
            'menus' => $this->content->editable($player),
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
                'required',
                'integer',
                Rule::exists('hotel_theme', 'theme_id')->where(
                    fn ($query) => $query->where('hotel_id', app(TenantContext::class)->id())
                ),
            ],
            'menus' => ['nullable', 'array'],
            'menus.*.key' => ['required', 'string', 'distinct', Rule::in($this->content->keys())],
            'menus.*.label' => ['required', 'string', 'max:100'],
            'menus.*.icon' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 _-]+$/'],
            'menus.*.icon_file' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'menus.*.remove_icon' => ['nullable', 'boolean'],
            'menus.*.placement' => ['required', Rule::in(['main', 'submenu'])],
            'menus.*.parent_menu_key' => ['nullable', 'string', Rule::in($this->content->keys())],
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

        $uploadedPaths = [];
        foreach ($menus as $index => &$menu) {
            $file = $request->file("menus.$index.icon_file");
            if (! $file) {
                continue;
            }

            $path = $file->storeAs(
                'images/player-menu-icons/'.$player->uuid,
                $menu['key'].'-'.Str::uuid().'.'.$file->extension(),
                'media'
            );

            if (! $path) {
                Storage::disk('media')->delete($uploadedPaths);
                throw ValidationException::withMessages([
                    "menus.$index.icon_file" => trans('common.player_content.icon_upload_failed'),
                ]);
            }

            $menu['_uploaded_icon_path'] = $path;
            $uploadedPaths[] = $path;
        }
        unset($menu);

        try {
            $obsoletePaths = $this->content->save(
                $player,
                (bool) $validated['use_custom_content'],
                $menus,
                (int) $validated['theme_id']
            );
        } catch (\Throwable $exception) {
            Storage::disk('media')->delete($uploadedPaths);
            throw $exception;
        }

        Storage::disk('media')->delete($obsoletePaths);

        try {
            $this->mqtt->publishPlayerUpdate($player->fresh(), 'menus');
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('players.content.edit', $player->uuid)
            ->with('success', trans('common.player_content.success'));
    }
}
