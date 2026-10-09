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

    public function test_it_fans_out_a_hotel_sync_to_each_active_player_serial(): void
    {
        $hotel = new Hotel(['code' => 'BIO-HOTEL']);
        $hotel->setRelation('configuration', new HotelConfiguration);
        $hotel->setRelation('players', collect([
            new Player(['serial' => 'BIO-TV-001', 'is_active' => true]),
            new Player(['serial' => 'BIO-TV-002', 'is_active' => true]),
            new Player(['serial' => 'BIO-TV-OFF', 'is_active' => false]),
        ]));

        $mqtt = Mockery::mock(MqttService::class);
        $configuration = Mockery::mock(HotelConfigurationManager::class);
        $configuration->shouldReceive('apply')->once()->with($hotel);
        $publishedSerials = [];
        $mqtt->shouldReceive('publish')->twice()->withArgs(
            function (string $topic, string $json, bool $retain) use (&$publishedSerials): bool {
                $payload = json_decode($json, true);
                $publishedSerials[] = $payload['player_serial'];

                return $topic === "hotel-app/hotels/BIO-HOTEL/players/{$payload['player_serial']}/update"
                    && $payload['type'] === 'movies'
                    && $payload['action'] === 'sync'
                    && $payload['hotel_code'] === 'BIO-HOTEL'
                    && in_array($payload['player_serial'], ['BIO-TV-001', 'BIO-TV-002'], true)
                    && $retain === false;
            }
        );

        $published = (new PlayerMqttRepository($mqtt, $configuration))->publishHotelUpdate($hotel, 'movies');

        $this->assertSame(2, $published);
        $this->assertEqualsCanonicalizing(['BIO-TV-001', 'BIO-TV-002'], $publishedSerials);
    }

    public function test_it_publishes_notification_to_each_player_notification_topic(): void
    {
        $hotel = new Hotel(['code' => 'NOTIF-HOTEL']);
        $hotel->setRelation('configuration', new HotelConfiguration);
        $player = new Player(['serial' => 'NOTIF-TV-001', 'is_active' => true]);
        $player->setRelation('hotel', $hotel);
        $mqtt = Mockery::mock(MqttService::class);
        $configuration = Mockery::mock(HotelConfigurationManager::class);
        $configuration->shouldReceive('apply')->once()->with($hotel);
        $mqtt->shouldReceive('publish')->once()->with(
            'hotel-app/hotels/NOTIF-HOTEL/players/NOTIF-TV-001/notification',
            Mockery::on(fn ($payload) => str_contains($payload, 'Uji MQTT')),
            false
        );
        $repository = new PlayerMqttRepository($mqtt, $configuration);

        $repository->publishPlayerNotification($player, [
            'type' => 'warning', 'title' => 'Uji MQTT', 'message' => 'Tes',
        ]);
    }
}
