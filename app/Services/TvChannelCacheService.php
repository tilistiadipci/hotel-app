<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class TvChannelCacheService
{
    private const VERSION_KEY = 'tv-channels:version';

    private const TTL_MINUTES = 5;

    public function remember(string $hotelId, Closure $callback)
    {
        $key = 'tv-channels:v'.$this->version().':hotel:'.$hotelId;

        return Cache::remember($key, now()->addMinutes(self::TTL_MINUTES), $callback);
    }

    /**
     * Bump the shared version so every hotel's cached channel list is
     * treated as stale, without having to enumerate and delete each key.
     */
    public function flush(): void
    {
        Cache::increment(self::VERSION_KEY);
    }

    private function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 0);
    }
}
