<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TvChannel;
use App\Services\PlayerContentManager;
use App\Services\PlayerTokenAuthenticator;
use App\Services\PlayerTvChannelManager;
use App\Services\TvChannelCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerTvChannelController extends Controller
{
    public function __construct(
        private readonly TvChannelCacheService $channelCache,
        private readonly PlayerTokenAuthenticator $authenticator,
        private readonly PlayerContentManager $content,
        private readonly PlayerTvChannelManager $channels,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $hotel = $request->attributes->get('hotel');
        $token = trim((string) $request->header('X-Player-Token'));

        if ($token === '') {
            return response()->json(['status' => false, 'message' => 'Header X-Player-Token wajib dikirim.'], 401);
        }

        $player = $this->authenticator->find($hotel, $token);

        if (! $player) {
            return response()->json(['status' => false, 'message' => 'Token player tidak valid atau sudah kedaluwarsa.'], 401);
        }

        $hotelChannels = $this->channelCache->remember($hotel->id, function () use ($hotel) {
            return TvChannel::query()
                ->assignedToHotel($hotel->id)
                ->with(['imageMedia', 'hotelImageMedia'])
                ->get();
        });

        $channels = $this->channels->effective($player, $hotelChannels)
            ->map(function (TvChannel $channel) {
                $streamUrl = $channel->custom_stream_url ?: $channel->stream_url;
                $remoteLogo = filter_var($channel->source_logo_url, FILTER_VALIDATE_URL)
                    && in_array(parse_url($channel->source_logo_url, PHP_URL_SCHEME), ['http', 'https'], true)
                        ? $channel->source_logo_url
                        : null;

                return [
                    'id' => $channel->uuid,
                    'name' => $channel->custom_name ?: $channel->name,
                    'group' => $channel->group_title,
                    'type' => $channel->custom_type ?: $channel->type,
                    'region' => $channel->custom_region ?: $channel->region,
                    'frequency' => $channel->custom_frequency ?: $channel->frequency,
                    'quality' => $channel->custom_quality ?: $channel->quality,
                    'stream_type' => $this->streamType($streamUrl),
                    'stream_url' => $streamUrl,
                    'logo_url' => $channel->hotelImageMedia
                        ? getMediaImageUrl($channel->hotelImageMedia->storage_path)
                        : ($channel->imageMedia
                        ? getMediaImageUrl($channel->imageMedia->storage_path)
                        : $remoteLogo),
                ];
            })
            ->values();

        return response()->json([
            'status' => true,
            'hotel' => ['code' => $hotel->code, 'name' => $hotel->name],
            'player' => ['id' => $player->uuid, 'name' => $player->name, 'alias' => $player->alias],
            'content' => [
                'uses_custom' => (bool) $player->use_custom_content,
                'menus' => $this->content->effective($player)->values(),
            ],
            'data' => $channels,
        ]);
    }

    private function streamType(?string $url): string
    {
        $path = strtolower((string) parse_url((string) $url, PHP_URL_PATH));

        return match (true) {
            str_ends_with($path, '.m3u8') => 'hls',
            str_ends_with($path, '.mpd') => 'dash',
            str_starts_with(strtolower((string) $url), 'udp://') => 'udp',
            default => 'http',
        };
    }
}
