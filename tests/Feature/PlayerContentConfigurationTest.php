<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\HotelLicense;
use App\Models\Player;
use App\Models\Setting;
use App\Repositories\PlayerMqttRepository;
use App\Services\PlayerContentManager;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class PlayerContentConfigurationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_player_uses_global_menu_settings_until_custom_content_is_enabled(): void
    {
        [$hotel, $licenseKey] = $this->hotelWithLicense();
        $player = $this->player($hotel);
        $this->setting($hotel, 'menu_music_label', 'Lagu Hotel');
        $this->setting($hotel, 'menu_music_status', 'inactive');

        $response = $this->getJson(route('api.player.configuration.show'), $this->headers($hotel, $licenseKey, $player));

        $response->assertOk()
            ->assertJsonPath('content.uses_custom', false)
            ->assertJsonPath('content.menus.3.key', 'music')
            ->assertJsonPath('content.menus.3.label', 'Lagu Hotel')
            ->assertJsonPath('content.menus.3.is_active', false)
            ->assertJsonPath('content.menus.3.source', 'global');
    }

    public function test_custom_player_content_overrides_label_icon_status_and_placement_in_api(): void
    {
        [$hotel, $licenseKey] = $this->hotelWithLicense();
        $player = $this->player($hotel);
        $menus = app(PlayerContentManager::class)->editable($player)->all();

        foreach ($menus as &$menu) {
            if ($menu['key'] === 'music') {
                $menu['label'] = 'Musik Kamar';
                $menu['icon'] = 'apps';
                $menu['placement'] = 'submenu';
                $menu['is_active'] = true;
                $menu['sort_order'] = 1;
            } else {
                $menu['sort_order'] += 10;
            }
        }
        unset($menu);

        app(PlayerContentManager::class)->save($player, true, $menus);

        $response = $this->getJson(route('api.player.configuration.show'), $this->headers($hotel, $licenseKey, $player));

        $response->assertOk()
            ->assertJsonPath('content.uses_custom', true)
            ->assertJsonPath('content.menus.0.key', 'music')
            ->assertJsonPath('content.menus.0.label', 'Musik Kamar')
            ->assertJsonPath('content.menus.0.icon', 'apps')
            ->assertJsonPath('content.menus.0.placement', 'submenu')
            ->assertJsonPath('content.menus.0.is_active', true)
            ->assertJsonPath('content.menus.0.source', 'player');
    }

    public function test_player_token_cannot_read_another_hotels_configuration(): void
    {
        [$hotel, $licenseKey] = $this->hotelWithLicense();
        [$otherHotel] = $this->hotelWithLicense();
        $foreignPlayer = $this->player($otherHotel);

        $this->getJson(route('api.player.configuration.show'), $this->headers($hotel, $licenseKey, $foreignPlayer))
            ->assertUnauthorized();
    }

    public function test_hotel_admin_can_save_player_content_and_trigger_menu_sync(): void
    {
        [$hotel] = $this->hotelWithLicense();
        app(TenantContext::class)->set($hotel->id);
        $player = $this->player($hotel);
        $menus = app(PlayerContentManager::class)->editable($player)
            ->map(function (array $menu): array {
                $menu['is_active'] = $menu['key'] === 'vod';
                $menu['label'] = $menu['key'] === 'vod' ? 'Film Pilihan' : $menu['label'];
                $menu['icon'] = $menu['key'] === 'vod' ? 'movie' : $menu['icon'];
                $menu['placement'] = $menu['key'] === 'vod' ? 'main' : 'submenu';

                return $menu;
            })
            ->all();

        $mqtt = Mockery::mock(PlayerMqttRepository::class);
        $mqtt->shouldReceive('publishPlayerUpdate')->once()->with(
            Mockery::on(fn (Player $published) => $published->is($player)),
            'menus'
        );
        $this->app->instance(PlayerMqttRepository::class, $mqtt);

        $this->withoutMiddleware()
            ->put(route('players.content.update', $player->uuid), [
                'use_custom_content' => '1',
                'menus' => $menus,
            ])
            ->assertRedirect(route('players.content.edit', $player->uuid));

        $this->assertTrue($player->fresh()->use_custom_content);
        $this->assertDatabaseHas('player_menu_settings', [
            'player_id' => $player->id,
            'menu_key' => 'vod',
            'label' => 'Film Pilihan',
            'icon' => 'movie',
            'placement' => 'main',
            'is_active' => true,
        ]);
    }

    private function hotelWithLicense(): array
    {
        $hotel = Hotel::query()->create([
            'code' => 'CONTENT-'.Str::upper(Str::random(6)),
            'name' => 'Content Test Hotel',
            'slug' => 'content-test-'.Str::lower(Str::random(8)),
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id_ID',
            'currency' => 'IDR',
            'status' => 'active',
            'is_active' => true,
        ]);
        $licenseKey = Str::upper(Str::random(6));
        HotelLicense::query()->create([
            'hotel_id' => $hotel->id,
            'plan_code' => 'test',
            'status' => HotelLicense::STATUS_ACTIVE,
            'starts_at' => now(),
            'license_key_hash' => Hash::make($licenseKey),
            'license_key_fingerprint' => HotelLicense::fingerprintFor($licenseKey),
        ]);

        return [$hotel, $licenseKey];
    }

    private function player(Hotel $hotel): Player
    {
        $player = new Player([
            'name' => 'Player Content',
            'alias' => 'Room 201',
            'serial' => 'CONTENT-PLAYER-'.Str::upper(Str::random(6)),
            'token' => 'content-token-'.Str::random(24),
            'token_expires_at' => now()->addDay(),
            'is_active' => true,
        ]);
        $player->hotel_id = $hotel->id;
        $player->save();

        return $player;
    }

    private function setting(Hotel $hotel, string $key, string $value): void
    {
        Setting::query()->withoutGlobalScope('hotel')->create([
            'hotel_id' => $hotel->id,
            'key' => $key,
            'name' => Str::headline($key),
            'value' => $value,
        ]);
    }

    private function headers(Hotel $hotel, string $licenseKey, Player $player): array
    {
        return [
            'X-Hotel-Code' => $hotel->code,
            'X-Hotel-License' => $licenseKey,
            'X-Player-Token' => $player->token,
        ];
    }
}
