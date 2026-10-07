<?php

namespace App\Services;

use App\Models\GuideItem;
use App\Models\MenuTenant;
use App\Models\Movie;
use App\Models\Place;
use App\Models\Player;
use App\Models\PlayerContentScope;
use App\Models\PlayerPublish;
use App\Models\Song;
use App\Repositories\PlayerMqttRepository;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlayerPublishManager
{
    public function __construct(
        private readonly PlayerContentManager $content,
        private readonly PlayerTvChannelManager $channels,
        private readonly PlayerOtherSettingsManager $otherSettings,
        private readonly PlayerMqttRepository $mqtt,
    ) {
    }

    public function publish(array $data, ?PlayerPublish $existing = null): PlayerPublish
    {
        $players = $this->resolvePlayers($data, $existing);
        $hotelId = app(TenantContext::class)->id();

        $publish = DB::transaction(function () use ($data, $players, $hotelId, $existing): PlayerPublish {
            $attributes = [
                'hotel_id' => $hotelId,
                'name' => $data['name'] ?? null,
                'theme_id' => $data['theme_id'] ?? null,
                'payload' => $this->snapshotPayload($data, $players),
                'published_at' => now(),
            ];

            if ($existing) {
                $existing->update($attributes);
                $publish = $existing;
            } else {
                $publish = PlayerPublish::query()->create($attributes + [
                    'created_by' => auth()->id(),
                ]);
            }

            $publish->targets()->sync($players->pluck('id')->all());

            foreach ($players as $player) {
                $this->content->save(
                    $player,
                    (bool) ($data['use_custom_content'] ?? false),
                    $data['menus'] ?? [],
                    isset($data['theme_id']) ? (int) $data['theme_id'] : null,
                );

                $this->channels->save(
                    $player,
                    (bool) ($data['use_custom_channels'] ?? false),
                    $data['channels'] ?? [],
                );

                $this->applyCatalogScopes($player, $data['catalogs'] ?? []);

                $this->otherSettings->save(
                    $player,
                    (bool) ($data['use_other_settings_override'] ?? false),
                    $data['other_settings'] ?? [],
                );
            }

            return $publish;
        });

        foreach ($players as $player) {
            try {
                $this->mqtt->publishPlayerUpdate($player->fresh(), 'all');
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $publish->load('targets');
    }

    public function resolvePlayers(array $data, ?PlayerPublish $existing = null): Collection
    {
        $mode = $data['target_mode'] ?? 'players';
        $query = Player::query()->where('is_active', true)->whereNull('deleted_at')->withoutContentFrom($existing);

        if ($mode === 'all') {
            return $query->orderBy('name')->get();
        }

        if ($mode === 'groups') {
            return $query
                ->whereIn('player_group_id', $data['target_group_ids'] ?? [])
                ->orderBy('name')
                ->get();
        }

        return $query
            ->whereIn('id', $data['target_player_ids'] ?? [])
            ->orderBy('name')
            ->get();
    }

    private function applyCatalogScopes(Player $player, array $catalogs): void
    {
        foreach (PlayerContentScope::TYPES as $type) {
            $catalog = $catalogs[$type] ?? ['mode' => 'all', 'ids' => []];
            $mode = $catalog['mode'] === 'selected' ? 'selected' : 'all';

            $scope = $player->contentScopes()->updateOrCreate(
                ['content_type' => $type],
                ['mode' => $mode],
            );

            $scope->items()->delete();

            if ($mode !== 'selected') {
                $this->syncTenantPivot($player, $type, []);
                continue;
            }

            $ids = $this->validCatalogIds($type, $catalog['ids'] ?? []);
            $scope->items()->createMany(
                collect($ids)->map(fn (int $id): array => ['content_id' => $id])->all()
            );

            $this->syncTenantPivot($player, $type, $ids);
        }
    }

    private function syncTenantPivot(Player $player, string $type, array $ids): void
    {
        if ($type !== 'menu_tenant') {
            return;
        }

        $player->menuTenants()->sync($ids);
    }

    private function validCatalogIds(string $type, array $ids): array
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();

        if (empty($ids)) {
            return [];
        }

        return $this->catalogQuery($type)
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function catalogQuery(string $type): Builder
    {
        return match ($type) {
            'menu_tenant' => MenuTenant::query(),
            'movie' => Movie::query(),
            'song' => Song::query(),
            'guide' => GuideItem::query(),
            'place' => Place::query(),
        };
    }

    private function snapshotPayload(array $data, Collection $players): array
    {
        return [
            'target_mode' => $data['target_mode'] ?? null,
            'target_group_ids' => $data['target_group_ids'] ?? [],
            'target_player_ids' => $data['target_player_ids'] ?? [],
            'resolved_player_ids' => $players->pluck('id')->all(),
            'use_custom_content' => (bool) ($data['use_custom_content'] ?? false),
            'menu_group_id' => $data['menu_group_id'] ?? null,
            'menus' => $data['menus'] ?? [],
            'use_custom_channels' => (bool) ($data['use_custom_channels'] ?? false),
            'channel_group_id' => $data['channel_group_id'] ?? null,
            'channels' => $data['channels'] ?? [],
            'content_group_id' => $data['content_group_id'] ?? null,
            'catalogs' => $data['catalogs'] ?? [],
            'use_other_settings_override' => (bool) ($data['use_other_settings_override'] ?? false),
            'other_settings' => $data['other_settings'] ?? [],
        ];
    }
}
