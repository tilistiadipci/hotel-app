<?php

namespace App\Http\Middleware;

use App\Models\Hotel;
use App\Models\Player;
use App\Repositories\PlayerMqttRepository;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PublishMasterDataUpdates
{
    private const ROUTE_TYPES = [
        'settings.' => 'configuration',
        'manager.hotels.settings.' => 'configuration',
        'themes.' => 'theme',
        'media.' => 'media',
        'movies.' => 'movies',
        'movie-categories.' => 'movies',
        'songs.' => 'music',
        'song-playlists.' => 'music',
        'places.' => 'places',
        'place-categories.' => 'places',
        'guides.' => 'guides',
        'guide-categories.' => 'guides',
        'running-texts.' => 'running_texts',
        'menu.' => 'menus',
        'menu-categories.' => 'menus',
        'menu-tenants.' => 'menus',
        'master-tvs.' => 'configuration',
        'player-groups.' => 'configuration',
        'players.' => 'configuration',
    ];

    public function __construct(
        private readonly PlayerMqttRepository $mqtt,
        private readonly TenantContext $tenant,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldPublish($request, $response)) {
            return $response;
        }

        $type = $this->typeForRoute($request);
        if (! $type) {
            return $response;
        }

        try {
            $player = $this->targetPlayer($request);
            if ($player) {
                $this->mqtt->publishPlayerUpdate($player, $type);
            } elseif (str_starts_with((string) $request->route()?->getName(), 'players.')) {
                // A player setting must never become an accidental hotel-wide broadcast.
                return $response;
            } elseif ($hotel = $this->targetHotel($request)) {
                $this->mqtt->publishHotelUpdate($hotel, $type);
            }
        } catch (Throwable $exception) {
            // Saving master data must still succeed when the broker is offline.
            report($exception);
        }

        return $response;
    }

    private function shouldPublish(Request $request, Response $response): bool
    {
        $newFlashKeys = session()->get('_flash.new', []);

        return ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)
            && $response->getStatusCode() < 400
            && ! array_intersect(['errors', 'error'], is_array($newFlashKeys) ? $newFlashKeys : [])
            && ($request->user()?->hasRoleCategory('manager', 'admin') ?? false);
    }

    private function typeForRoute(Request $request): ?string
    {
        $routeName = (string) $request->route()?->getName();

        if ($routeName === 'settings.update' && str_starts_with((string) $request->input('section'), 'customize_menu')) {
            return 'menus';
        }

        foreach (self::ROUTE_TYPES as $prefix => $type) {
            if (str_starts_with($routeName, $prefix)) {
                return $type;
            }
        }

        return null;
    }

    private function targetPlayer(Request $request): ?Player
    {
        if (! str_starts_with((string) $request->route()?->getName(), 'players.')) {
            return null;
        }

        $value = $request->route('player');
        if ($value instanceof Player) {
            return $value;
        }

        return $value
            ? Player::query()->where('uuid', (string) $value)->first()
            : null;
    }

    private function targetHotel(Request $request): ?Hotel
    {
        $routeHotel = $request->route('hotel');
        if ($routeHotel instanceof Hotel) {
            return $routeHotel->is_system ? null : $routeHotel;
        }

        $activeHotel = $request->attributes->get('active_hotel');
        if ($activeHotel instanceof Hotel) {
            return $activeHotel->is_system ? null : $activeHotel;
        }

        $hotel = $this->tenant->hotel();

        return $hotel && ! $hotel->is_system ? $hotel : null;
    }
}
