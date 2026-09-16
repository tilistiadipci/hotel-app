<?php

namespace App\Repositories;

use App\Models\Hotel;
use App\Models\Player;
use App\Services\MqttService;
use App\Tenancy\HotelConfigurationManager;
use InvalidArgumentException;

class PlayerMqttRepository
{
    public const UPDATE_TYPES = [
        'checkin',
        'checkout',
        'tv_channels',
        'menus',
        'theme',
        'configuration',
        'application',
        'all',
    ];

    public function __construct(
        private readonly MqttService $mqtt,
        private readonly HotelConfigurationManager $configurationManager,
    ) {
    }

    public function publishPlayerUpdate(Player $player, string $type, string $action = 'sync'): void
    {
        $player->loadMissing('hotel.configuration');
        $hotel = $player->hotel;
        if (! $hotel) {
            throw new InvalidArgumentException('Player is not assigned to a hotel.');
        }
        $this->publishUpdate($hotel, (string) $player->serial, $type, $action);
    }

    public function publishHotelUpdate(Hotel $hotel, string $type, string $action = 'sync'): void
    {
        $hotel->loadMissing('configuration');
        $this->publishUpdate($hotel, 'all', $type, $action);
    }

    public function subscribePlayerUpdates(Hotel $hotel, string $playerSerial, callable $handler): void
    {
        $hotel->loadMissing('configuration');
        $this->configurationManager->apply($hotel);
        $this->mqtt->subscribe($this->updateTopic($hotel->code, $playerSerial), $handler);
    }

    public function updateTopic(string $hotelCode, string $playerSerial): string
    {
        $this->assertTopicSegment($hotelCode, 'hotel code');
        $this->assertTopicSegment($playerSerial, 'player serial');

        return "hotel-app/hotels/{$hotelCode}/players/{$playerSerial}/update";
    }

    private function publishUpdate(Hotel $hotel, string $playerSerial, string $type, string $action): void
    {
        if (! in_array($type, self::UPDATE_TYPES, true)) {
            throw new InvalidArgumentException("Unsupported player MQTT update type [{$type}].");
        }

        $this->configurationManager->apply($hotel);
        $payload = json_encode([
            'type' => $type,
            'action' => $action,
            'hotel_code' => $hotel->code,
            'player_serial' => $playerSerial,
            'timestamp' => now()->toIso8601String(),
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $this->mqtt->publish($this->updateTopic($hotel->code, $playerSerial), $payload, false);
    }

    private function assertTopicSegment(string $value, string $name): void
    {
        if ($value === '' || str_contains($value, '/') || str_contains($value, '+') || str_contains($value, '#')) {
            throw new InvalidArgumentException("Invalid MQTT {$name} [{$value}].");
        }
    }
}
