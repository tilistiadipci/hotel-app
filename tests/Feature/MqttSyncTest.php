<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Player;
use App\Models\Role;
use App\Models\User;
use App\Repositories\PlayerMqttRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class MqttSyncTest extends TestCase
{
    use DatabaseTransactions;

    public function test_only_superadmin_can_open_manual_mqtt_sync_page(): void
    {
        $this->actingAs($this->user('superadmin'))
            ->get(route('platform.mqtt-sync.index'))
            ->assertOk()
            ->assertSee('Sync to TV MQTT')
            ->assertSee('Notifikasi / alarm (Uji coba) (notification)', false);

        $this->actingAs($this->user('admin', $this->hotel()))
            ->get(route('platform.mqtt-sync.index'))
            ->assertForbidden();
    }

    public function test_superadmin_can_sync_a_type_to_all_players_in_a_hotel(): void
    {
        $hotel = $this->hotel();
        $mqtt = Mockery::mock(PlayerMqttRepository::class);
        $mqtt->shouldReceive('publishHotelUpdate')
            ->once()
            ->with(Mockery::on(fn (Hotel $target) => $target->is($hotel)), 'tv_channels');
        $this->app->instance(PlayerMqttRepository::class, $mqtt);

        $this->actingAs($this->user('superadmin'))
            ->post(route('platform.mqtt-sync.store'), [
                'hotel_id' => $hotel->id,
                'type' => 'tv_channels',
            ])
            ->assertSessionHas('success');
    }

    public function test_superadmin_can_sync_a_type_to_one_player(): void
    {
        $hotel = $this->hotel();
        $player = Player::query()->create([
            'hotel_id' => $hotel->id,
            'name' => 'TV Kamar 101',
            'serial' => 'TV-'.Str::upper(Str::random(8)),
            'is_active' => true,
        ]);
        $mqtt = Mockery::mock(PlayerMqttRepository::class);
        $mqtt->shouldReceive('publishPlayerUpdate')
            ->once()
            ->with(Mockery::on(fn (Player $target) => $target->is($player)), 'theme');
        $this->app->instance(PlayerMqttRepository::class, $mqtt);

        $this->actingAs($this->user('superadmin'))
            ->post(route('platform.mqtt-sync.store'), [
                'hotel_id' => $hotel->id,
                'player_id' => $player->id,
                'type' => 'theme',
            ])
            ->assertSessionHas('success');
    }

    public function test_superadmin_can_send_a_test_notification_to_one_player(): void
    {
        $hotel = $this->hotel();
        $player = Player::query()->create([
            'hotel_id' => $hotel->id,
            'name' => 'TV Uji Notifikasi',
            'serial' => 'NOTIF-'.Str::upper(Str::random(8)),
            'is_active' => true,
        ]);
        $mqtt = Mockery::mock(PlayerMqttRepository::class);
        $mqtt->shouldReceive('publishPlayerNotification')
            ->once()
            ->with(Mockery::on(fn (Player $target) => $target->is($player)), Mockery::on(
                fn (array $payload) => $payload['type'] === 'notification'
                    && $payload['title'] === 'Uji Notifikasi MQTT'
            ));
        $this->app->instance(PlayerMqttRepository::class, $mqtt);

        $this->actingAs($this->user('superadmin'))
            ->post(route('platform.mqtt-sync.store'), [
                'hotel_id' => $hotel->id,
                'player_id' => $player->id,
                'type' => 'notification',
            ])
            ->assertSessionHas('success');
    }

    public function test_manager_master_data_change_automatically_syncs_all_hotel_players(): void
    {
        $hotel = $this->hotel();
        $manager = $this->user('manager');
        $manager->managedHotels()->attach($hotel->id, ['is_active' => true]);

        $mqtt = Mockery::mock(PlayerMqttRepository::class);
        $mqtt->shouldReceive('publishHotelUpdate')
            ->once()
            ->with(Mockery::on(fn (Hotel $target) => $target->is($hotel)), 'configuration');
        $this->app->instance(PlayerMqttRepository::class, $mqtt);

        $this->actingAs($manager)
            ->put(route('manager.hotels.settings.update', $hotel), [
                'settings' => ['general_app_name' => 'Hotel MQTT Updated'],
            ])
            ->assertRedirect();
    }

    private function hotel(): Hotel
    {
        $code = 'SYNC-'.Str::upper(Str::random(6));

        return Hotel::query()->create([
            'code' => $code,
            'name' => $code,
            'slug' => Str::lower($code).'-'.Str::lower(Str::random(6)),
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id_ID',
            'currency' => 'IDR',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    private function user(string $category, ?Hotel $hotel = null): User
    {
        $role = new Role();
        $role->name = 'MQTT '.ucfirst($category).' '.Str::random(6);
        $role->category = $category;
        $role->save();

        return User::query()->create([
            'hotel_id' => $hotel?->id,
            'username' => $category.'_'.Str::lower(Str::random(8)),
            'email' => Str::lower(Str::random(8)).'@example.test',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }
}
