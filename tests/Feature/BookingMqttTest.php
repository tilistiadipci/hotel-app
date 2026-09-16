<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Player;
use App\Repositories\PlayerMqttRepository;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class BookingMqttTest extends TestCase
{
    use DatabaseTransactions;

    public function test_checkin_and_checkout_publish_player_mqtt_updates(): void
    {
        $hotel = Hotel::query()->create([
            'code' => 'MQTT-'.Str::upper(Str::random(6)),
            'name' => 'MQTT Test Hotel',
            'slug' => 'mqtt-test-'.Str::lower(Str::random(6)),
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id_ID',
            'currency' => 'IDR',
            'status' => 'active',
            'is_active' => true,
        ]);
        app(TenantContext::class)->set($hotel->id);

        $player = Player::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Player MQTT',
            'serial' => 'MQTT-PLAYER-'.Str::upper(Str::random(6)),
            'is_active' => true,
        ]);

        $mqtt = Mockery::mock(PlayerMqttRepository::class);
        $mqtt->shouldReceive('publishPlayerUpdate')->once()->with(
            Mockery::on(fn (Player $published) => $published->is($player)),
            'checkin'
        );
        $mqtt->shouldReceive('publishPlayerUpdate')->once()->with(
            Mockery::on(fn (Player $published) => $published->is($player)),
            'checkout'
        );
        $this->app->instance(PlayerMqttRepository::class, $mqtt);

        $this->withoutMiddleware()
            ->postJson(route('booking.store', $player->uuid), ['guest_name' => 'Tamu MQTT'])
            ->assertOk()->assertJson(['status' => true]);

        $this->withoutMiddleware()
            ->postJson(route('booking.checkout', $player->uuid))
            ->assertOk()->assertJson(['status' => true]);

        $this->assertDatabaseHas('bookings', [
            'player_id' => $player->id,
            'guest_name' => 'Tamu MQTT',
        ]);
        $this->assertNotNull($player->bookings()->firstOrFail()->checked_out_at);
    }
}
