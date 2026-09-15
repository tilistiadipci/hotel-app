<?php

namespace App\Services;

use App\Models\TvChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class M3uPlaylistService
{
    public function parse(string $contents): array
    {
        $lines = preg_split('/\R/u', preg_replace('/^\xEF\xBB\xBF/', '', $contents)) ?: [];
        $channels = [];
        $pending = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, '#EXTINF:')) {
                $pending = $this->parseExtinf($line);

                continue;
            }

            if ($pending && ! str_starts_with($line, '#') && $this->isSupportedStreamUrl($line)) {
                $pending['stream_url'] = $line;
                $pending['source_hash'] = hash('sha256', Str::lower($pending['name']).'|'.$line);
                $channels[] = $pending;
                $pending = null;
            }
        }

        return collect($channels)
            ->unique(fn ($channel) => $channel['tvg_id'] ?: $channel['source_hash'])
            ->values()->all();
    }

    public function markExisting(array $channels, string $masterHotelId): array
    {
        return collect($channels)->map(function (array $channel) use ($masterHotelId) {
            $channel['existing'] = $this->findExisting($channel, $masterHotelId) !== null;

            return $channel;
        })->all();
    }

    public function import(array $channels, string $masterHotelId): array
    {
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($channels, $masterHotelId, &$created, &$updated): void {
            foreach ($channels as $index => $data) {
                $channel = $this->findExisting($data, $masterHotelId);
                $payload = [
                    'name' => $data['name'],
                    'tvg_id' => $data['tvg_id'] ?: null,
                    'group_title' => $data['group_title'] ?: null,
                    'source_type' => 'm3u',
                    'source_logo_url' => $data['source_logo_url'] ?: null,
                    'source_hash' => $data['source_hash'],
                    'type' => 'streaming',
                    'region' => $this->inferRegion($data['group_title']),
                    'stream_url' => $data['stream_url'],
                    'quality' => $this->inferQuality($data['name']),
                    'sort_order' => $channel?->sort_order ?? $index,
                    'is_active' => true,
                ];

                if ($channel) {
                    $channel->update($payload);
                    $updated++;

                    continue;
                }

                $payload['slug'] = $this->uniqueSlug($data['tvg_id'] ?: $data['name'], $masterHotelId);
                $channel = new TvChannel($payload);
                $channel->hotel_id = $masterHotelId;
                $channel->save();
                $created++;
            }
        });

        return compact('created', 'updated');
    }

    private function parseExtinf(string $line): array
    {
        [$metadata, $label] = array_pad(explode(',', $line, 2), 2, '');
        preg_match_all('/([a-zA-Z0-9_-]+)="([^"]*)"/', $metadata, $matches, PREG_SET_ORDER);
        $attributes = collect($matches)->mapWithKeys(fn ($match) => [$match[1] => trim($match[2])]);
        // Teks setelah koma adalah nama tampilan playlist (mis. "RCTI").
        // tvg-name/tvg-id lebih sering merupakan identifier untuk EPG.
        $name = trim((string) ($label ?: $attributes->get('tvg-name') ?: $attributes->get('tvg-id') ?: 'Unnamed Channel'));

        return [
            'name' => mb_substr($name, 0, 150),
            'tvg_id' => mb_substr((string) $attributes->get('tvg-id', ''), 0, 180),
            'group_title' => mb_substr((string) $attributes->get('group-title', ''), 0, 150),
            'source_logo_url' => mb_substr((string) $attributes->get('tvg-logo', ''), 0, 1000),
        ];
    }

    private function findExisting(array $data, string $masterHotelId): ?TvChannel
    {
        return TvChannel::query()->withoutGlobalScope('hotel')
            ->where('hotel_id', $masterHotelId)->whereNull('deleted_at')
            ->where(function ($query) use ($data) {
                if (! empty($data['tvg_id'])) {
                    $query->where('tvg_id', $data['tvg_id'])->orWhere('slug', Str::slug($data['tvg_id']));
                } else {
                    $query->where('source_hash', $data['source_hash']);
                }
            })->first();
    }

    private function uniqueSlug(string $value, string $masterHotelId): string
    {
        $base = Str::slug($value) ?: 'channel';
        $slug = $base;
        $suffix = 2;

        while (TvChannel::query()->withoutGlobalScope('hotel')->where('hotel_id', $masterHotelId)->where('slug', $slug)->withTrashed()->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return mb_substr($slug, 0, 180);
    }

    private function inferRegion(?string $group): string
    {
        return preg_match('/indonesia|nasional|local/i', (string) $group) ? 'national' : 'international';
    }

    private function inferQuality(string $name): ?string
    {
        return match (true) {
            (bool) preg_match('/\b4k\b/i', $name) => '4K',
            (bool) preg_match('/\bhd\b/i', $name) => 'HD',
            (bool) preg_match('/\bsd\b/i', $name) => 'SD',
            default => null,
        };
    }

    private function isSupportedStreamUrl(string $url): bool
    {
        return (bool) preg_match('/^(https?|rtsp|rtmp|udp):\/\//i', $url);
    }
}
