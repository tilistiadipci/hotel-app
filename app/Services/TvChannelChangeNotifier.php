<?php

namespace App\Services;

use App\Models\Hotel;
use App\Repositories\PlayerMqttRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Invalidates the player channel-list cache and tells connected players
 * (via MQTT) to resync, whenever a hotel's assigned TV channels change.
 */
class TvChannelChangeNotifier
{
    public function __construct(
        private TvChannelCacheService $cache,
        private PlayerMqttRepository $playerMqtt,
    ) {
    }

    public function notifyHotel(string $hotelId): void
    {
        $this->notifyHotels([$hotelId]);
    }

    /**
     * @param  iterable<string>  $hotelIds
     */
    public function notifyHotels(iterable $hotelIds): void
    {
        $hotelIds = collect($hotelIds)->filter()->unique()->values();

        if ($hotelIds->isEmpty()) {
            return;
        }

        $this->cache->flush();

        $this->hotels($hotelIds)->each(fn (Hotel $hotel) => $this->publishSync($hotel));
    }

    /**
     * Resolve every hotel currently assigned to the given master channel,
     * so master-catalog edits notify all hotels serving that channel.
     */
    public function hotelIdsAssignedTo(string|int $channelId): Collection
    {
        return DB::table('hotel_tv_channel')
            ->where('tv_channel_id', $channelId)
            ->pluck('hotel_id');
    }

    private function hotels(Collection $hotelIds): Collection
    {
        return Hotel::query()
            ->with('configuration')
            ->whereIn('id', $hotelIds)
            ->get();
    }

    private function publishSync(Hotel $hotel): void
    {
        try {
            $this->playerMqtt->publishHotelUpdate($hotel, 'tv_channels');
        } catch (Throwable $e) {
            report($e);
        }
    }
}
