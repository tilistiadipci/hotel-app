<?php

namespace App\Services;

use App\Models\Player;
use App\Models\TvChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlayerTvChannelManager
{
    /**
     * The hotel's own assigned+active channel list, in the hotel's display
     * order. This is what a player shows when it hasn't been customized.
     */
    public function hotelChannels(Player $player): Collection
    {
        return TvChannel::query()
            ->assignedToHotel((string) $player->hotel_id)
            ->get();
    }

    /**
     * The channels this specific player actually shows: the hotel's list,
     * or - if the player has its own custom selection turned on - only the
     * channels it picked, in its own order. Pass an already-fetched
     * $hotelChannels (e.g. from a cache) to avoid querying it twice.
     */
    public function effective(Player $player, ?Collection $hotelChannels = null): Collection
    {
        $channels = $hotelChannels ?? $this->hotelChannels($player);

        if (! $player->use_custom_channels) {
            return $channels->values();
        }

        // A channel's own hotel_id (who created/owns it) can differ from
        // the hotel it's assigned to (e.g. catalog channels owned by the
        // Master hotel) - bypass TvChannel's tenant scope so those still
        // resolve here, same as assignedToHotel() already does.
        $selected = $player->tvChannels()
            ->withoutGlobalScope('hotel')
            ->wherePivot('is_active', true)
            ->get()
            ->keyBy('id');

        return $channels
            ->filter(fn (TvChannel $channel) => $selected->has($channel->id))
            ->sortBy(fn (TvChannel $channel) => $selected->get($channel->id)->pivot->sort_order)
            ->values();
    }

    /**
     * The hotel's channel list annotated with this player's current
     * selection, for prefilling the edit form. A channel the player has
     * never customized defaults to selected, so turning "custom" on for
     * the first time starts from "everything the hotel already shows".
     */
    public function editable(Player $player): Collection
    {
        $channels = $this->hotelChannels($player);
        $overrides = $player->tvChannels()->withoutGlobalScope('hotel')->get()->keyBy('id');

        return $channels->values()->map(function (TvChannel $channel, int $index) use ($overrides): array {
            $override = $overrides->get($channel->id);

            return [
                'id' => $channel->id,
                'uuid' => $channel->uuid,
                'name' => $channel->custom_name ?: $channel->name,
                'group' => $channel->group_title,
                'type' => $channel->custom_type ?: $channel->type,
                'region' => $channel->custom_region ?: $channel->region,
                'is_selected' => $override ? (bool) $override->pivot->is_active : true,
                'sort_order' => $override ? (int) $override->pivot->sort_order : $index,
            ];
        })->sortBy('sort_order')->values();
    }

    /**
     * @param  array<int, array{tv_channel_id: int|string, is_active?: bool, sort_order?: int}>  $channels
     */
    public function save(Player $player, bool $useCustom, array $channels): void
    {
        DB::transaction(function () use ($player, $useCustom, $channels): void {
            $player->use_custom_channels = $useCustom;
            $player->updated_by = auth()->id();
            $player->save();

            if (! $useCustom) {
                return;
            }

            $syncData = [];
            foreach ($channels as $index => $channel) {
                $syncData[(int) $channel['tv_channel_id']] = [
                    'is_active' => (bool) ($channel['is_active'] ?? false),
                    'sort_order' => (int) ($channel['sort_order'] ?? $index),
                ];
            }

            $player->tvChannels()->sync($syncData);
        });
    }
}
