<?php

namespace App\Http\Controllers;

use App\Models\GuideItem;
use App\Models\MenuTenant;
use App\Models\Movie;
use App\Models\Place;
use App\Models\Player;
use App\Models\PlayerContentScope;
use App\Models\PlayerPublish;
use App\Models\PublishChannelGroup;
use App\Models\PublishContentGroup;
use App\Models\PublishMenuGroup;
use App\Models\Song;
use App\Models\TvChannel;
use App\Repositories\PlayerGroupRepository;
use App\Repositories\PlayerRepository;
use App\Repositories\ThemeRepository;
use App\Services\PlayerContentManager;
use App\Services\PlayerOtherSettingsManager;
use App\Services\PlayerPublishManager;
use App\Services\PlayerTvChannelManager;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class PlayerPublishController extends Controller
{
    public function __construct(
        private readonly PlayerRepository $players,
        private readonly PlayerGroupRepository $groups,
        private readonly ThemeRepository $themes,
        private readonly PlayerContentManager $content,
        private readonly PlayerTvChannelManager $channels,
        private readonly PlayerOtherSettingsManager $otherSettings,
        private readonly PlayerPublishManager $publisher,
    ) {
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PlayerPublish::query()
                ->with(['theme', 'targets'])
                ->withCount('targets')
                ->latest('published_at')
                ->latest('id');

            return DataTables::of($query)
                ->addColumn('published_at_display', fn (PlayerPublish $publish): string => optional($publish->published_at)->format('d M Y H:i') ?? '-')
                ->addColumn('target_players', function (PlayerPublish $publish): string {
                    $names = $publish->targets->pluck('name')->filter()->values();

                    if ($names->isEmpty()) {
                        return '-';
                    }

                    $visible = $names->take(4)->implode(', ');
                    if ($names->count() > 4) {
                        $visible .= ' '.trans('common.publish.more_players', ['count' => $names->count() - 4]);
                    }

                    return e($visible);
                })
                ->addColumn('theme_name', fn (PlayerPublish $publish): string => e($publish->theme?->name ?? '-'))
                ->addColumn('action', function (PlayerPublish $publish): string {
                    $showUrl = route('publish.show', $publish);
                    $editUrl = route('publish.edit', $publish);
                    $deleteTitle = trans('common.delete');
                    $detailTitle = trans('common.detail');
                    $editTitle = trans('common.edit');

                    return <<<HTML
                        <div class="d-inline-flex align-items-center action-compact">
                            <a href="{$showUrl}" class="action-pill neutral bg-primary text-white" title="{$detailTitle}" data-toggle="tooltip">
                                <i class="fa fa-eye"></i>
                            </a>
                            <a href="{$editUrl}" class="action-pill neutral" title="{$editTitle}" data-toggle="tooltip">
                                <i class="fa fa-edit"></i>
                            </a>
                            <button type="button" class="action-pill danger" title="{$deleteTitle}" data-toggle="tooltip" onclick="deletePublish({$publish->id})">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    HTML;
                })
                ->rawColumns(['action', 'target_players', 'theme_name'])
                ->make(true);
        }

        return view('pages.publish.index', [
            'page' => 'publish',
            'icon' => 'fa fa-paper-plane',
        ]);
    }

    public function create()
    {
        return view('pages.publish.create', $this->formData());
    }

    public function edit(PlayerPublish $publish)
    {
        return view('pages.publish.create', $this->formData($publish));
    }

    private function formData(?PlayerPublish $publish = null): array
    {
        $draftPlayer = new Player([
            'hotel_id' => app(TenantContext::class)->id(),
            'use_custom_content' => true,
            'use_custom_channels' => true,
        ]);

        $themes = $this->themes->getList();
        $payload = $publish?->payload ?? [];
        $defaultTheme = $themes->first(fn ($theme) => (string) ($theme->is_default ?? '0') === '1')
            ?? $themes->first();
        $menus = ! empty($payload['menus']) ? collect($payload['menus']) : $this->content->editable($draftPlayer);
        $channels = $this->channels->editable($draftPlayer);

        if (! empty($payload['channels'])) {
            $publishedChannels = collect($payload['channels'])->keyBy('tv_channel_id');
            $channels = $channels->map(function (array $channel) use ($publishedChannels): array {
                $published = $publishedChannels->get($channel['id']);

                if (! $published) {
                    return $channel;
                }

                $channel['is_selected'] = (bool) ($published['is_active'] ?? false);
                $channel['sort_order'] = $published['sort_order'] ?? $channel['sort_order'];

                return $channel;
            });
        }

        return [
            'page' => 'publish',
            'icon' => 'fa fa-paper-plane',
            'publish' => $publish,
            'publishPayload' => $payload,
            'menuGroups' => PublishMenuGroup::query()->with('items')->where('is_active', true)->orderBy('name')->get(),
            'channelGroups' => PublishChannelGroup::query()->with('items.tvChannel')->where('is_active', true)->orderBy('name')->get(),
            'contentGroups' => PublishContentGroup::query()->with('items')->where('is_active', true)->orderBy('name')->get(),
            'formAction' => $publish ? route('publish.update', $publish) : route('publish.store'),
            'formMethod' => $publish ? 'PUT' : 'POST',
            'submitLabel' => $publish ? trans('common.publish.update_publish') : trans('common.publish.title'),
            'playerGroups' => $this->groups->query()
                ->with(['players' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
                ->withCount(['players' => fn ($query) => $query->where('is_active', true)])
                ->orderBy('name')
                ->get(),
            'players' => $this->players->query()
                ->with('playerGroup')
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'themeOptions' => $themes->map(fn ($theme): array => [
                'id' => $theme->id,
                'name' => $theme->name,
                'description' => $theme->description,
                'is_default' => (string) ($theme->is_default ?? '0') === '1',
                'image_url' => $theme->imageMedia
                    ? getMediaImageUrl($theme->imageMedia->storage_path, 480, 270)
                    : asset('images/theme_'.$theme->id.'.png'),
            ]),
            'selectedThemeId' => old('theme_id', $publish?->theme_id ?? $defaultTheme?->id),
            'menus' => $menus,
            'iconOptions' => $this->content->iconOptions(),
            'channels' => $channels,
            'catalogTypes' => PlayerContentScope::TYPES,
            'otherSettingValues' => $this->otherSettings->globalValues(),
            'catalogs' => [
                'menu_tenant' => MenuTenant::query()->where('is_active', true)->orderBy('name')->get(),
                'movie' => Movie::query()->where('is_active', true)->orderBy('title')->get(),
                'song' => Song::query()->with(['artist', 'album'])->where('is_active', true)->orderBy('title')->get(),
                'guide' => GuideItem::query()->where('is_active', true)->orderBy('title')->get(),
                'place' => Place::query()->where('is_active', true)->orderBy('name')->get(),
            ],
        ];
    }

    public function store(Request $request)
    {
        $validated = $this->validatedPublishData($request);
        $publish = $this->publisher->publish($validated);

        return redirect()
            ->route('publish.show', $publish)
            ->with('success', trans('common.publish.success', ['count' => $publish->targets->count()]));
    }

    public function update(Request $request, PlayerPublish $publish)
    {
        $validated = $this->validatedPublishData($request);
        $publish = $this->publisher->publish($validated, $publish);

        return redirect()
            ->route('publish.show', $publish)
            ->with('success', trans('common.publish.update_success', ['count' => $publish->targets->count()]));
    }

    private function validatedPublishData(Request $request): array
    {
        $request->merge([
            'use_custom_content' => $request->boolean('use_custom_content'),
            'use_custom_channels' => $request->boolean('use_custom_channels'),
        ]);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'target_mode' => ['required', Rule::in(['all', 'groups', 'players'])],
            'target_group_ids' => ['nullable', 'array'],
            'target_group_ids.*' => ['integer', 'exists:player_groups,id'],
            'target_player_ids' => ['nullable', 'array'],
            'target_player_ids.*' => ['integer', 'exists:players,id'],
            'theme_id' => [
                'required',
                'integer',
                Rule::exists('hotel_theme', 'theme_id')->where(
                    fn ($query) => $query->where('hotel_id', app(TenantContext::class)->id())
                ),
            ],
            'menu_group_id' => [
                'nullable',
                'integer',
                Rule::exists('publish_menu_groups', 'id')->where(fn ($query) => $query->where('hotel_id', app(TenantContext::class)->id())),
            ],
            'save_menu_group_name' => ['nullable', 'string', 'max:120'],
            'use_custom_content' => ['required', 'boolean'],
            'menus' => ['nullable', 'array', 'max:100'],
            'menus.*.key' => ['required', 'string', 'distinct', 'max:50', 'regex:/^[a-z][a-z0-9_-]*$/'],
            'menus.*.label' => ['required', 'string', 'max:100'],
            'menus.*.icon' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 _-]+$/'],
            'menus.*.placement' => ['required', Rule::in(['main', 'submenu'])],
            'menus.*.parent_menu_key' => ['nullable', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_-]*$/'],
            'menus.*.is_active' => ['required', 'boolean'],
            'menus.*.sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'use_custom_channels' => ['required', 'boolean'],
            'channels' => ['nullable', 'array', 'max:200'],
            'channels.*.tv_channel_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('hotel_tv_channel', 'tv_channel_id')->where(
                    fn ($query) => $query->where('hotel_id', app(TenantContext::class)->id())
                ),
            ],
            'channels.*.is_active' => ['required', 'boolean'],
            'channels.*.sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'channel_group_id' => [
                'nullable',
                'integer',
                Rule::exists('publish_channel_groups', 'id')->where(fn ($query) => $query->where('hotel_id', app(TenantContext::class)->id())),
            ],
            'save_channel_group_name' => ['nullable', 'string', 'max:120'],
            'content_group_id' => [
                'nullable',
                'integer',
                Rule::exists('publish_content_groups', 'id')->where(fn ($query) => $query->where('hotel_id', app(TenantContext::class)->id())),
            ],
            'save_content_group_name' => ['nullable', 'string', 'max:120'],
            'catalogs' => ['nullable', 'array'],
            'catalogs.*.mode' => ['required', Rule::in(PlayerContentScope::MODES)],
            'catalogs.*.ids' => ['nullable', 'array'],
            'catalogs.*.ids.*' => ['integer', 'min:1'],
            'use_other_settings_override' => ['nullable', 'boolean'],
            'other_settings' => ['nullable', 'array'],
            'other_settings.about_phone' => ['nullable', 'string', 'max:100'],
            'other_settings.about_email' => ['nullable', 'string', 'max:150'],
            'other_settings.about_website' => ['nullable', 'string', 'max:150'],
            'other_settings.about_ssid' => ['nullable', 'string', 'max:100'],
            'other_settings.about_wifi_password' => ['nullable', 'string', 'max:100'],
        ]);

        $validated['use_other_settings_override'] = $request->boolean('use_other_settings_override');
        $validated = $this->applySelectedGroups($validated);

        if ($validated['target_mode'] === 'groups' && empty($validated['target_group_ids'])) {
            throw ValidationException::withMessages(['target_group_ids' => trans('common.publish.target_group_required')]);
        }

        if ($validated['target_mode'] === 'players' && empty($validated['target_player_ids'])) {
            throw ValidationException::withMessages(['target_player_ids' => trans('common.publish.target_player_required')]);
        }

        $this->validateMenuParents($validated['menus'] ?? []);

        $players = $this->publisher->resolvePlayers($validated);
        if ($players->isEmpty()) {
            throw ValidationException::withMessages(['target_mode' => trans('common.publish.no_target_player')]);
        }

        $this->saveRequestedGroups($validated);

        return $validated;
    }

    private function applySelectedGroups(array $data): array
    {
        if (! empty($data['menu_group_id'])) {
            $group = PublishMenuGroup::query()->with('items')->findOrFail($data['menu_group_id']);
            $data['menus'] = $group->items->map(fn ($item): array => [
                'key' => $item->menu_key,
                'label' => $item->label,
                'icon' => $item->icon,
                'placement' => $item->placement,
                'parent_menu_key' => $item->parent_menu_key,
                'is_active' => (bool) $item->is_active,
                'sort_order' => (int) $item->sort_order,
            ])->all();
        }

        if (! empty($data['channel_group_id'])) {
            $group = PublishChannelGroup::query()->with('items')->findOrFail($data['channel_group_id']);
            $data['channels'] = $group->items->map(fn ($item): array => [
                'tv_channel_id' => (int) $item->tv_channel_id,
                'is_active' => (bool) $item->is_active,
                'sort_order' => (int) $item->sort_order,
            ])->all();
        }

        if (! empty($data['content_group_id'])) {
            $group = PublishContentGroup::query()->with('items')->findOrFail($data['content_group_id']);
            $data['catalogs'] = $group->items
                ->groupBy('content_type')
                ->map(function (Collection $items): array {
                    $mode = $items->first()?->mode === 'selected' ? 'selected' : 'all';

                    return [
                        'mode' => $mode,
                        'ids' => $mode === 'selected'
                            ? $items->pluck('content_id')->filter()->map(fn ($id) => (int) $id)->values()->all()
                            : [],
                    ];
                })
                ->all();
        }

        return $data;
    }

    private function saveRequestedGroups(array $data): void
    {
        if (! empty($data['save_menu_group_name']) && ! empty($data['menus'])) {
            $group = PublishMenuGroup::query()->updateOrCreate(
                ['name' => $data['save_menu_group_name']],
                [
                    'hotel_id' => app(TenantContext::class)->id(),
                    'is_active' => true,
                ],
            );
            $group->items()->delete();
            $group->items()->createMany(collect($data['menus'])->map(fn (array $menu): array => [
                'menu_key' => $menu['key'],
                'label' => $menu['label'],
                'icon' => $menu['icon'] ?? null,
                'placement' => $menu['placement'],
                'parent_menu_key' => $menu['placement'] === 'submenu' ? ($menu['parent_menu_key'] ?? null) : null,
                'is_active' => (bool) ($menu['is_active'] ?? false),
                'sort_order' => (int) ($menu['sort_order'] ?? 0),
            ])->all());
        }

        if (! empty($data['save_channel_group_name']) && ! empty($data['channels'])) {
            $group = PublishChannelGroup::query()->updateOrCreate(
                ['name' => $data['save_channel_group_name']],
                [
                    'hotel_id' => app(TenantContext::class)->id(),
                    'is_active' => true,
                ],
            );
            $group->items()->delete();
            $group->items()->createMany(collect($data['channels'])->map(fn (array $channel): array => [
                'tv_channel_id' => (int) $channel['tv_channel_id'],
                'is_active' => (bool) ($channel['is_active'] ?? false),
                'sort_order' => (int) ($channel['sort_order'] ?? 0),
            ])->all());
        }

        if (! empty($data['save_content_group_name'])) {
            $group = PublishContentGroup::query()->updateOrCreate(
                ['name' => $data['save_content_group_name']],
                [
                    'hotel_id' => app(TenantContext::class)->id(),
                    'is_active' => true,
                ],
            );
            $group->items()->delete();

            $items = [];
            foreach (PlayerContentScope::TYPES as $type) {
                $catalog = $data['catalogs'][$type] ?? ['mode' => 'all', 'ids' => []];
                $mode = ($catalog['mode'] ?? 'all') === 'selected' ? 'selected' : 'all';
                $ids = collect($catalog['ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values();

                if ($mode === 'selected' && $ids->isNotEmpty()) {
                    foreach ($ids as $id) {
                        $items[] = [
                            'content_type' => $type,
                            'mode' => 'selected',
                            'content_id' => $id,
                        ];
                    }
                    continue;
                }

                $items[] = [
                    'content_type' => $type,
                    'mode' => 'all',
                    'content_id' => null,
                ];
            }

            $group->items()->createMany($items);
        }
    }

    public function show(PlayerPublish $publish)
    {
        $publish->load(['theme', 'targets.playerGroup']);

        return view('pages.publish.show', [
            'page' => 'publish',
            'icon' => 'fa fa-paper-plane',
            'publish' => $publish,
            'details' => $this->publishDetails($publish),
        ]);
    }

    public function destroy(PlayerPublish $publish)
    {
        $publish->delete();

        if (request()->ajax()) {
            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete'),
            ]);
        }

        return redirect()
            ->route('publish.index')
            ->with('success', trans('common.success.delete'));
    }

    private function validateMenuParents(array $menus): void
    {
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
    }

    private function publishDetails(PlayerPublish $publish): array
    {
        $payload = $publish->payload ?? [];
        $catalogs = $payload['catalogs'] ?? [];

        return [
            'target_mode' => $payload['target_mode'] ?? '-',
            'menus' => collect($payload['menus'] ?? [])->sortBy('sort_order')->values(),
            'channels' => $this->publishedChannels($payload['channels'] ?? []),
            'catalogs' => $this->publishedCatalogs($catalogs),
            'use_custom_content' => (bool) ($payload['use_custom_content'] ?? false),
            'use_custom_channels' => (bool) ($payload['use_custom_channels'] ?? false),
            'use_other_settings_override' => (bool) ($payload['use_other_settings_override'] ?? false),
            'other_settings' => $payload['other_settings'] ?? [],
        ];
    }

    private function publishedChannels(array $channels): Collection
    {
        $activeChannels = collect($channels)
            ->filter(fn (array $channel): bool => (bool) ($channel['is_active'] ?? false))
            ->sortBy('sort_order')
            ->values();

        $names = TvChannel::query()
            ->whereIn('id', $activeChannels->pluck('tv_channel_id')->filter()->all())
            ->pluck('name', 'id');

        return $activeChannels->map(fn (array $channel): array => [
            'id' => (int) ($channel['tv_channel_id'] ?? 0),
            'name' => $names->get((int) ($channel['tv_channel_id'] ?? 0), '-'),
            'sort_order' => $channel['sort_order'] ?? 0,
        ]);
    }

    private function publishedCatalogs(array $catalogs): array
    {
        $labels = [
            'menu_tenant' => trans('common.publish.catalog.menu_tenant'),
            'movie' => trans('common.publish.catalog.movie'),
            'song' => trans('common.publish.catalog.song'),
            'guide' => trans('common.publish.catalog.guide'),
            'place' => trans('common.publish.catalog.place'),
        ];

        return collect(PlayerContentScope::TYPES)
            ->mapWithKeys(function (string $type) use ($catalogs, $labels): array {
                $catalog = $catalogs[$type] ?? ['mode' => 'all', 'ids' => []];
                $mode = ($catalog['mode'] ?? 'all') === 'selected' ? 'selected' : 'all';
                $ids = collect($catalog['ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->values();

                return [$type => [
                    'label' => $labels[$type] ?? $type,
                    'mode' => $mode,
                    'items' => $mode === 'selected' ? $this->catalogNames($type, $ids->all()) : collect(),
                ]];
            })
            ->all();
    }

    private function catalogNames(string $type, array $ids): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        $query = match ($type) {
            'menu_tenant' => MenuTenant::query()->whereIn('id', $ids)->pluck('name', 'id'),
            'movie' => Movie::query()->whereIn('id', $ids)->pluck('title', 'id'),
            'song' => Song::query()->whereIn('id', $ids)->pluck('title', 'id'),
            'guide' => GuideItem::query()->whereIn('id', $ids)->pluck('title', 'id'),
            'place' => Place::query()->whereIn('id', $ids)->pluck('name', 'id'),
        };

        return collect($ids)->map(fn (int $id): string => $query->get($id, '#'.$id))->values();
    }
}
