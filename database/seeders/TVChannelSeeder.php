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
            [
                'name'        => 'SCTV',
                'slug'        => 'sctv',
                'logo_path'   => 'images/no-image.png',
                'type'        => 'digital',
                'region'      => 'national',
                'stream_url'  => null,
                'frequency'   => 'UHF 24',
                'quality'     => 'HD',
                'sort_order'  => 1,
            ],
            [
                'name'        => 'TVRI',
                'slug'        => 'tvri',
                'logo_path'   => 'images/no-image.png',
                'type'        => 'digital',
                'region'      => 'national',
                'stream_url'  => null,
                'frequency'   => 'UHF 43',
                'quality'     => 'HD',
                'sort_order'  => 2,
            ],
            [
                'name'        => 'Netflix',
                'slug'        => 'netflix',
                'logo_path'   => 'images/no-image.png',
                'type'        => 'streaming',
                'region'      => 'international',
                'stream_url'  => 'netflix.com',
                'frequency'   => null,
                'quality'     => 'HD',
                'sort_order'  => 10,
            ],
            [
                'name'        => 'Disney+',
                'slug'        => 'disney-plus',
                'logo_path'   => 'images/no-image.png',
                'type'        => 'streaming',
                'region'      => 'international',
                'stream_url'  => 'disneyplus.com',
                'frequency'   => null,
                'quality'     => 'HD',
                'sort_order'  => 11,
            ],
            [
                'name'        => 'Vidio',
                'slug'        => 'vidio',
                'logo_path'   => 'images/no-image.png',
                'type'        => 'streaming',
                'region'      => 'national',
                'stream_url'  => 'vidio.com',
                'frequency'   => null,
                'quality'     => 'HD',
                'sort_order'  => 12,
            ],
        ];

        $rows = [];
        foreach ($channels as $ch) {
            $mediaId = DB::table('medias')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => $ch['name'] . ' Logo',
                'original_filename' => basename($ch['logo_path']),
                'type' => 'image',
                'extension' => pathinfo($ch['logo_path'], PATHINFO_EXTENSION),
                'storage_path' => $ch['logo_path'],
                'mime_type' => 'image/png',
                'size' => null,
                'duration' => null,
                'width' => null,
                'height' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $rows[] = [
                'uuid'        => (string) Str::uuid(),
                'name'        => $ch['name'],
                'slug'        => $ch['slug'],
                'type'        => $ch['type'],
                'region'      => $ch['region'],
                'stream_url'  => $ch['stream_url'],
                'frequency'   => $ch['frequency'],
                'quality'     => $ch['quality'],
                'sort_order'  => $ch['sort_order'],
                'is_active'   => true,
                'image_id'    => $mediaId,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        DB::table('tv_channels')->insert($rows);
    }
}
