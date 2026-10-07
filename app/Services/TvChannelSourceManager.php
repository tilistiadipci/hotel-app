<?php

namespace App\Services;

use App\Models\TvChannel;
use App\Models\TvChannelSource;
use Illuminate\Support\Facades\Storage;

/**
 * Manages the extra stream sources a TV channel can accumulate over
 * multiple M3U imports, and keeps the channel's own stream_url in sync
 * with the current primary (first active) source so every existing
 * reader of stream_url keeps working unchanged.
 */
class TvChannelSourceManager
{
    public function attach(TvChannel $channel, string $streamUrl, ?string $label = null, ?string $entryText = null): TvChannelSource
    {
        $entryText = $this->normalizeEntryText($entryText ?: $streamUrl);
        $hash = hash('sha256', $entryText);
        $existing = $channel->sources()->where('source_hash', $hash)->first();

        if ($existing) {
            $this->syncPlaylist($channel);

            return $existing;
        }

        $nextOrder = (int) $channel->sources()->max('sort_order');

        $source = $channel->sources()->create([
            'label' => $label,
            'stream_url' => $streamUrl,
            'entry_text' => $entryText,
            'source_hash' => $hash,
            'sort_order' => $nextOrder + 1,
            'is_active' => true,
        ]);

        $this->syncPlaylist($channel);

        return $source;
    }

    public function detach(TvChannel $channel, int $sourceId): void
    {
        $channel->sources()->where('id', $sourceId)->delete();

        $this->syncPlaylist($channel);
    }

    public function syncPrimary(TvChannel $channel): void
    {
        $this->syncPlaylist($channel);
    }

    public function syncPlaylist(TvChannel $channel): void
    {
        $primary = $channel->sources()->where('is_active', true)->orderBy('sort_order')->first();

        if (! $primary) {
            return;
        }

        $path = $this->playlistPath($channel);
        $contents = $channel->sources()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (TvChannelSource $source) => $this->normalizeEntryText($source->entry_text ?: $source->stream_url))
            ->implode("\n\n");

        Storage::disk('public')->put($path, "#EXTM3U\n".$contents."\n");

        if ($channel->stream_url !== $path) {
            $channel->update(['stream_url' => $path]);
        }
    }

    private function playlistPath(TvChannel $channel): string
    {
        return 'm3ustream/'.($channel->slug ?: 'channel-'.$channel->id).'.m3u8';
    }

    private function normalizeEntryText(string $entryText): string
    {
        $lines = preg_split('/\R/u', trim($entryText)) ?: [];

        return collect($lines)
            ->map(fn ($line) => rtrim((string) $line))
            ->filter(fn ($line) => $line !== '')
            ->implode("\n");
    }
}
