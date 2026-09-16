<?php

namespace Tests\Unit;

use App\Models\Hotel;
use App\Models\HotelConfiguration;
use App\Models\Player;
use App\Repositories\PlayerMqttRepository;
use App\Services\MqttService;
use App\Tenancy\HotelConfigurationManager;
use Mockery;
use Tests\TestCase;

class PlayerMqttRepositoryTest extends TestCase
{
    public function test_it_publishes_checkin_update_to_the_target_player(): void
    {
        $hotel = new Hotel(['code' => 'BIO-HOTEL']);
        $hotel->setRelation('configuration', new HotelConfiguration);
        $player = new Player(['serial' => 'BIO-TV-001']);
        $player->setRelation('hotel', $hotel);

        $mqtt = Mockery::mock(MqttService::class);
        $configuration = Mockery::mock(HotelConfigurationManager::class);
        $configuration->shouldReceive('apply')->once()->with($hotel);
        $mqtt->shouldReceive('publish')->once()->with(
            'hotel-app/hotels/BIO-HOTEL/players/BIO-TV-001/update',
            Mockery::on(function (string $json): bool {
                $payload = json_decode($json, true);

                return $payload['type'] === 'checkin'
                    && $payload['action'] === 'sync'
                    && $payload['hotel_code'] === 'BIO-HOTEL'
                    && $payload['player_serial'] === 'BIO-TV-001'
                    && ! empty($payload['timestamp']);
            }),
            false
        );

        (new PlayerMqttRepository($mqtt, $configuration))->publishPlayerUpdate($player, 'checkin');
    }

    public function test_it_can_subscribe_to_a_player_update_topic(): void
    {
        $hotel = new Hotel(['code' => 'BIO-HOTEL']);
        $hotel->setRelation('configuration', new HotelConfiguration);
        $handler = static fn () => null;

        $mqtt = Mockery::mock(MqttService::class);
        $configuration = Mockery::mock(HotelConfigurationManager::class);
        $configuration->shouldReceive('apply')->once()->with($hotel);
        $mqtt->shouldReceive('subscribe')->once()->with(
            'hotel-app/hotels/BIO-HOTEL/players/BIO-TV-001/update',
            $handler
        );

        (new PlayerMqttRepository($mqtt, $configuration))
            ->subscribePlayerUpdates($hotel, 'BIO-TV-001', $handler);
    }
}
