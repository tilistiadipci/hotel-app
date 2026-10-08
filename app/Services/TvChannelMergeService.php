<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\TvChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class TvChannelMergeService
{
    public function __construct(private TvChannelSourceManager $sources) {}

    /**
     * @param  array<int, string>  $channelUids
     * @return array{channel: TvChannel, source_count: int, affected_hotel_ids: Collection}
     */
    public function merge(array $channelUids, string $name, bool $deleteSources = false): array
    {
        $masterId = Hotel::masterId();
        $channels = TvChannel::query()
            ->withoutGlobalScope('hotel')
            ->where('hotel_id', $masterId)
            ->whereIn('uuid', $channelUids)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with(['sources' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->get();

        if ($channels->count() !== count(array_unique($channelUids))) {
            throw ValidationException::withMessages([
                'channel_uids' => __('platform.tv_catalog.merge_invalid_selection'),
            ]);
        }

        $entries = $channels->flatMap(fn (TvChannel $channel) => $this->entriesFor($channel));

        if ($entries->isEmpty()) {
            throw ValidationException::withMessages([
                'channel_uids' => __('platform.tv_catalog.merge_no_streams'),
            ]);
        }

        $affectedHotelIds = $deleteSources
            ? DB::table('hotel_tv_channel')->whereIn('tv_channel_id', $channels->pluck('id'))->pluck('hotel_id')->unique()->values()
            : collect();
        $merged = null;

        try {
            DB::transaction(function () use ($channels, $entries, $masterId, $name, $deleteSources, &$merged): void {
                $first = $channels->first();
                $merged = new TvChannel([
                    'name' => $name,
                    'slug' => $this->uniqueSlug($name),
                    'group_title' => $channels->pluck('group_title')->filter()->unique()->count() === 1
                        ? $channels->pluck('group_title')->filter()->first()
                        : null,
                    'source_type' => 'merged',
                    'source_logo_url' => $first->source_logo_url,
                    'type' => 'streaming',
                    'region' => $channels->pluck('region')->unique()->count() === 1 ? $first->region : 'national',
                    'sort_order' => ((int) TvChannel::query()->withoutGlobalScope('hotel')->where('hotel_id', $masterId)->max('sort_order')) + 1,
                    'is_active' => true,
                    'image_id' => $first->image_id,
                    'created_by' => auth()->id(),
                ]);
                $merged->hotel_id = $masterId;
                $merged->save();

                foreach ($entries as $entry) {
                    $this->sources->attach(
                        $merged,
                        $entry['stream_url'],
                        $entry['label'],
                        $entry['entry_text'],
                        false
                    );
                }

                $this->sources->syncPlaylist($merged);

                if ($deleteSources) {
                    TvChannel::query()
                        ->withoutGlobalScope('hotel')
                        ->whereIn('id', $channels->pluck('id'))
                        ->update([
                            'deleted_by' => auth()->id(),
                            'deleted_at' => now(),
                        ]);
                }
            });
        } catch (Throwable $exception) {
            if ($merged) {
                Storage::disk('public')->delete('m3ustream/'.$merged->slug.'.m3u8');
            }

            throw $exception;
        }

        return [
            'channel' => $merged,
            'source_count' => $merged->sources()->count(),
            'affected_hotel_ids' => $affectedHotelIds,
        ];
    }

    private function entriesFor(TvChannel $channel): Collection
    {
        if ($channel->sources->isEmpty()) {
            if (! filled($channel->stream_url)) {
                return collect();
            }

            return collect([$this->entry($channel, $channel->stream_url, $channel->stream_url, $channel->name)]);
        }

        return $channel->sources->map(fn ($source) => $this->entry(
            $channel,
            $source->stream_url,
            $source->entry_text ?: $source->stream_url,
            $source->label ? $channel->name.' - '.$source->label : $channel->name
        ));
    }

    private function entry(TvChannel $channel, string $streamUrl, string $entryText, string $label): array
    {
        $lines = collect(preg_split('/\R/u', trim($entryText)) ?: [])
            ->map(fn ($line) => rtrim((string) $line))
            ->filter(fn ($line) => $line !== '' && ! preg_match('/^#EXTM3U(?:\s|$)/i', $line))
            ->values();

        if (! $lines->contains(fn ($line) => str_starts_with(strtoupper($line), '#EXTINF:'))) {
            $lines->prepend($this->extinf($channel));
        }

        return [
            'stream_url' => $streamUrl,
            'entry_text' => $lines->implode("\n"),
            'label' => Str::limit($label, 100, ''),
        ];
    }

    private function extinf(TvChannel $channel): string
    {
        $attributes = collect([
            'tvg-logo' => $channel->source_logo_url,
            'tvg-id' => $channel->tvg_id,
            'group-title' => $channel->group_title,
        ])->filter(fn ($value) => filled($value))
            ->map(fn ($value, $key) => $key.'="'.str_replace('"', '', (string) $value).'"')
            ->implode(' ');

        return '#EXTINF:-1'.($attributes ? ' '.$attributes : '').','.str_replace(["\r", "\n"], '', $channel->name);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'merged-channel';
        $slug = mb_substr($base, 0, 180);
        $suffix = 2;

        while (TvChannel::query()->withoutGlobalScope('hotel')->withTrashed()->where('slug', $slug)->exists()) {
            $tail = '-'.$suffix++;
            $slug = mb_substr($base, 0, 180 - mb_strlen($tail)).$tail;
        }

        return $slug;
    }
}
