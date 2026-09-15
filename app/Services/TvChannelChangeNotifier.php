<?php

namespace App\Services;

use App\Models\Hotel;
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
        private MqttService $mqtt,
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

        $this->hotelCodes($hotelIds)->each(fn (string $code) => $this->publishSync($code));
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

    private function hotelCodes(Collection $hotelIds): Collection
    {
        return Hotel::query()
            ->whereIn('id', $hotelIds)
            ->pluck('code');
    }

    private function publishSync(string $hotelCode): void
    {
        try {
            $this->mqtt->publish(
                "hotel-app/hotels/{$hotelCode}/players/all/update",
                json_encode([
                    'type' => 'tv_channels',
                    'action' => 'sync',
                    'timestamp' => now()->toIso8601String(),
                ]),
                false
            );
        } catch (Throwable $e) {
            report($e);
        }
    }
}
