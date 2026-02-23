<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TVChannelSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $channels = [
            // Digital TV
            [
                'uuid'        => (string) Str::uuid(),
                'name'        => 'SCTV',
                'slug'        => 'sctv',
                'logo'        => '/images/tv/sctv.png',
                'type'        => 'digital',
                'region'      => 'national',
                'stream_url'  => null,
                'frequency'   => 'UHF 24',
                'quality'     => 'HD',
                'sort_order'  => 1,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'uuid'        => (string) Str::uuid(),
                'name'        => 'TVRI',
                'slug'        => 'tvri',
                'logo'        => '/images/tv/tvri.png',
                'type'        => 'digital',
                'region'      => 'national',
                'stream_url'  => null,
                'frequency'   => 'UHF 43',
                'quality'     => 'HD',
                'sort_order'  => 2,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            // Streaming services
            [
                'uuid'        => (string) Str::uuid(),
                'name'        => 'Netflix',
                'slug'        => 'netflix',
                'logo'        => '/images/tv/netflix.png',
                'type'        => 'streaming',
                'region'      => 'international',
                'stream_url'  => 'netflix.com',
                'frequency'   => null,
                'quality'     => 'HD',
                'sort_order'  => 10,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'uuid'        => (string) Str::uuid(),
                'name'        => 'Disney+',
                'slug'        => 'disney-plus',
                'logo'        => '/images/tv/disney.png',
                'type'        => 'streaming',
                'region'      => 'international',
                'stream_url'  => 'disneyplus.com',
                'frequency'   => null,
                'quality'     => 'HD',
                'sort_order'  => 11,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'uuid'        => (string) Str::uuid(),
                'name'        => 'Vidio',
                'slug'        => 'vidio',
                'logo'        => '/images/tv/vidio.png',
                'type'        => 'streaming',
                'region'      => 'national',
                'stream_url'  => 'vidio.com',
                'frequency'   => null,
                'quality'     => 'HD',
                'sort_order'  => 12,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        DB::table('tv_channels')->upsert(
            $channels,
            ['slug'],
            [
                'logo',
                'type',
                'region',
                'stream_url',
                'frequency',
                'quality',
                'sort_order',
                'is_active',
                'updated_at',
            ]
        );
    }
}
