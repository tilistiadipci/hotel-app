<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\HotelLicense;
use App\Models\Player;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\TvChannel;
use App\Repositories\PlayerMqttRepository;
use App\Services\PlayerContentManager;
use App\Services\PlayerTvChannelManager;
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
            ->assertJsonPath('content.html', fn ($html) => is_string($html) && str_contains($html, 'Musik Kamar'))
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

    public function test_custom_channel_editor_does_not_select_new_unconfigured_hotel_channels(): void
    {
        [$hotel] = $this->hotelWithLicense();
        app(TenantContext::class)->set($hotel->id);
        $player = $this->player($hotel);
        $player->update(['use_custom_channels' => true]);

        $selected = $this->tvChannel($hotel, 'Selected Channel');
        $newChannel = $this->tvChannel($hotel, 'New Channel');
        $player->tvChannels()->attach($selected->id, [
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $editable = app(PlayerTvChannelManager::class)->editable($player->fresh())->keyBy('id');

        $this->assertTrue($editable->get($selected->id)['is_selected']);
        $this->assertFalse($editable->get($newChannel->id)['is_selected']);
    }

    public function test_global_channel_editor_selects_new_hotel_channels_by_default(): void
    {
        [$hotel] = $this->hotelWithLicense();
        app(TenantContext::class)->set($hotel->id);
        $player = $this->player($hotel);
        $channel = $this->tvChannel($hotel, 'Global Channel');

        $editable = app(PlayerTvChannelManager::class)->editable($player)->keyBy('id');

        $this->assertTrue($editable->get($channel->id)['is_selected']);
    }

    public function test_hotel_admin_can_publish_player_content_and_trigger_sync(): void
    {
        [$hotel] = $this->hotelWithLicense();
        app(TenantContext::class)->set($hotel->id);
        $player = $this->player($hotel);
        $theme = $this->theme($hotel);
        $menus = app(PlayerContentManager::class)->editable($player)
            ->map(function (array $menu): array {
                $menu['is_active'] = $menu['key'] === 'vod';
                $menu['label'] = $menu['key'] === 'vod' ? 'Film Pilihan' : $menu['label'];
                $menu['icon'] = $menu['key'] === 'vod' ? 'movie' : $menu['icon'];
                $menu['placement'] = $menu['key'] === 'vod' ? 'main' : 'submenu';
                $menu['parent_menu_key'] = $menu['key'] === 'vod' ? null : 'vod';

                return $menu;
            })
            ->all();

        $mqtt = Mockery::mock(PlayerMqttRepository::class);
        $mqtt->shouldReceive('publishPlayerUpdate')->once()->with(
            Mockery::on(fn (Player $published) => $published->is($player)),
            'all'
        );
        $this->app->instance(PlayerMqttRepository::class, $mqtt);

        $response = $this->withoutMiddleware()
            ->post(route('publish.store'), [
                'target_mode' => 'players',
                'target_player_ids' => [$player->id],
                'theme_id' => $theme->id,
                'use_custom_content' => '1',
                'menus' => $menus,
                'use_custom_channels' => '0',
                'use_other_settings_override' => '0',
            ])
            ->assertRedirect();

        $this->assertTrue($player->fresh()->use_custom_content);
        $publishId = basename($response->headers->get('Location'));
        $this->assertDatabaseHas('player_publishes', [
            'id' => $publishId,
            'hotel_id' => $hotel->id,
            'theme_id' => $theme->id,
        ]);
        $this->assertDatabaseHas('player_publish_targets', [
            'player_id' => $player->id,
        ]);
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

    private function theme(Hotel $hotel): Theme
    {
        $theme = Theme::query()->create([
            'name' => 'Publish Test Theme',
            'description' => 'Theme for publish tests',
            'is_default' => true,
            'is_active' => true,
        ]);

        $hotel->themes()->attach($theme->id, ['is_default' => true]);

        return $theme;
    }

    private function tvChannel(Hotel $hotel, string $name): TvChannel
    {
        $channel = new TvChannel([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(8)),
            'type' => 'streaming',
            'region' => 'national',
            'stream_url' => 'https://example.test/'.Str::slug($name).'.m3u8',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $channel->hotel_id = $hotel->id;
        $channel->save();

        $hotel->tvChannels()->attach($channel->id, [
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return $channel;
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
