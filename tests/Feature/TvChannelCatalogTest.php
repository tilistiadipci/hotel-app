<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\HotelLicense;
use App\Models\Player;
use App\Models\Role;
use App\Models\TvChannel;
use App\Models\User;
use App\Services\M3uPlaylistService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class TvChannelCatalogTest extends TestCase
{
    use DatabaseTransactions;

    public function test_superadmin_can_assign_a_different_shared_catalog_to_each_hotel(): void
    {
        $masterId = Hotel::masterId();
        $first = $this->channel($masterId, 'News One');
        $second = $this->channel($masterId, 'Sports One');
        $hotelA = $this->hotel('TV-A');
        $hotelB = $this->hotel('TV-B');

        $this->actingAs($this->user('superadmin'))
            ->put(route('platform.hotels.tv-channels.update', $hotelA), ['channel_ids' => [$first->id]])
            ->assertRedirect(route('platform.hotels.edit', ['hotel' => $hotelA, 'tab' => 'tv-channels']));

        $this->actingAs($this->user('superadmin'))
            ->put(route('platform.hotels.tv-channels.update', $hotelB), ['channel_ids' => [$second->id]])
            ->assertRedirect(route('platform.hotels.edit', ['hotel' => $hotelB, 'tab' => 'tv-channels']));

        $this->assertDatabaseHas('hotel_tv_channel', ['hotel_id' => $hotelA->id, 'tv_channel_id' => $first->id]);
        $this->assertDatabaseMissing('hotel_tv_channel', ['hotel_id' => $hotelA->id, 'tv_channel_id' => $second->id]);
        $this->assertDatabaseHas('hotel_tv_channel', ['hotel_id' => $hotelB->id, 'tv_channel_id' => $second->id]);
        $this->assertDatabaseMissing('hotel_tv_channel', ['hotel_id' => $hotelB->id, 'tv_channel_id' => $first->id]);
    }

    public function test_hotel_admin_only_sees_assigned_channels_and_can_customize_assignment(): void
    {
        $masterId = Hotel::masterId();
        $assigned = $this->channel($masterId, 'Assigned Channel');
        $hidden = $this->channel($masterId, 'Hidden Channel');
        $hotel = $this->hotel('TV-C');
        $hotel->tvChannels()->attach($assigned->id, ['is_active' => true, 'sort_order' => 2]);
        $admin = $this->user('admin', $hotel);

        $response = $this->actingAs($admin)->getJson(route('tv-channels.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $response->assertOk()->assertSee('Assigned Channel')->assertDontSee('Hidden Channel');

        $this->actingAs($admin)->patch(route('tv-channels.assignment.update', $assigned->uuid), [
            'custom_name' => 'Channel Kamar',
            'custom_type' => 'streaming',
            'custom_region' => 'international',
            'custom_stream_url' => 'https://hotel.example.test/channel.m3u8',
            'custom_frequency' => 'IPTV',
            'custom_quality' => '4K',
            'sort_order' => 7,
            'is_active' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('hotel_tv_channel', [
            'hotel_id' => $hotel->id,
            'tv_channel_id' => $assigned->id,
            'custom_name' => 'Channel Kamar',
            'custom_type' => 'streaming',
            'custom_region' => 'international',
            'custom_stream_url' => 'https://hotel.example.test/channel.m3u8',
            'custom_frequency' => 'IPTV',
            'custom_quality' => '4K',
            'sort_order' => 7,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('tv-channels.assignment.edit', $assigned->uuid))
            ->assertOk()
            ->assertSee('Kelola Channel Hotel')
            ->assertSee('name="image"', false);
    }

    public function test_manager_can_only_filter_channels_assigned_by_superadmin(): void
    {
        $masterId = Hotel::masterId();
        $first = $this->channel($masterId, 'Manager News');
        $second = $this->channel($masterId, 'Manager Sports');
        $managedHotel = $this->hotel('TV-MANAGED');
        $otherHotel = $this->hotel('TV-OTHER');
        $manager = $this->user('manager');
        $manager->managedHotels()->attach($managedHotel->id, ['is_active' => true]);
        $managedHotel->tvChannels()->attach($first->id, ['is_active' => true, 'sort_order' => 1]);

        $this->actingAs($manager)
            ->get(route('manager.tv-channels.index', ['hotel_id' => $managedHotel->id]))
            ->assertOk()
            ->assertSee('Akses TV Channels Hotel')
            ->assertSee('Manager News')
            ->assertDontSee('Manager Sports');

        $this->actingAs($manager)
            ->put(route('manager.tv-channels.update', $managedHotel), ['channel_ids' => []])
            ->assertRedirect(route('manager.tv-channels.index', ['hotel_id' => $managedHotel->id]));

        $this->assertDatabaseHas('hotel_tv_channel', [
            'hotel_id' => $managedHotel->id,
            'tv_channel_id' => $first->id,
            'is_active' => false,
        ]);

        $this->actingAs($manager)
            ->put(route('manager.tv-channels.update', $managedHotel), ['channel_ids' => [$second->id]])
            ->assertSessionHasErrors('channel_ids.0');

        $this->assertDatabaseMissing('hotel_tv_channel', [
            'hotel_id' => $managedHotel->id,
            'tv_channel_id' => $second->id,
        ]);

        $this->actingAs($manager)
            ->put(route('manager.tv-channels.update', $otherHotel), ['channel_ids' => [$second->id]])
            ->assertNotFound();
    }

    public function test_m3u_import_updates_an_existing_channel_without_creating_a_duplicate(): void
    {
        $service = app(M3uPlaylistService::class);
        $masterId = Hotel::masterId();
        $firstPlaylist = <<<'M3U'
#EXTM3U
#EXTINF:-1 tvg-id="news.id" tvg-name="News ID" tvg-logo="https://example.test/news.png" group-title="Indonesia",News ID
https://stream.example.test/news.m3u8
M3U;
        $updatedPlaylist = str_replace('News ID', 'News ID HD', $firstPlaylist);

        $firstResult = $service->import($service->parse($firstPlaylist), $masterId);
        $secondResult = $service->import($service->parse($updatedPlaylist), $masterId);

        $this->assertSame(1, $firstResult['created']);
        $this->assertSame(0, $secondResult['created']);
        $this->assertSame(1, $secondResult['updated']);
        $this->assertSame(1, TvChannel::query()->withoutGlobalScope('hotel')
            ->where('hotel_id', $masterId)->where('tvg_id', 'news.id')->count());
        $this->assertDatabaseHas('tv_channels', [
            'hotel_id' => $masterId,
            'tvg_id' => 'news.id',
            'name' => 'News ID HD',
            'quality' => 'HD',
        ]);
    }

    public function test_m3u_parser_uses_display_label_and_reads_remote_logo(): void
    {
        $playlist = <<<'M3U'
#EXTM3U
#EXTINF:-1 tvg-id="rcti.id" tvg-name="RCTI.id" tvg-logo="https://example.test/rcti.png" group-title="Indonesia",RCTI
https://stream.example.test/rcti.m3u8
M3U;

        $channels = app(M3uPlaylistService::class)->parse($playlist);

        $this->assertCount(1, $channels);
        $this->assertSame('RCTI', $channels[0]['name']);
        $this->assertSame('https://example.test/rcti.png', $channels[0]['source_logo_url']);
    }

    public function test_m3u_preview_redirects_to_a_refreshable_get_page(): void
    {
        $playlist = <<<'M3U'
#EXTM3U
#EXTINF:-1 tvg-id="preview.id" tvg-logo="https://example.test/preview.png",Preview Channel
https://stream.example.test/preview.m3u8
M3U;
        $superadmin = $this->user('superadmin');

        $response = $this->actingAs($superadmin)->post(route('tv-channels.import.preview'), [
            'playlist' => UploadedFile::fake()->createWithContent('preview.m3u8', $playlist),
        ]);

        $response->assertRedirect();
        $previewUrl = $response->headers->get('Location');
        $this->assertStringContainsString('/tv-channels/import/preview/', $previewUrl);

        $this->actingAs($superadmin)->get($previewUrl)
            ->assertOk()
            ->assertSee('Preview Channel');

        $this->actingAs($superadmin)->get(route('tv-channels.import.preview.stale'))
            ->assertRedirect(route('tv-channels.import'));
    }

    public function test_direct_m3u_import_creates_each_channel_in_the_master_table(): void
    {
        $playlist = <<<'M3U'
#EXTM3U
#EXTINF:-1 tvg-id="direct-one.id",Direct One
https://stream.example.test/direct-one.m3u8
#EXTINF:-1 tvg-id="direct-two.id",Direct Two
https://stream.example.test/direct-two.m3u8
M3U;

        $this->actingAs($this->user('superadmin'))
            ->post(route('tv-channels.import.preview'), [
                'direct_import' => 1,
                'playlist' => UploadedFile::fake()->createWithContent('direct.m3u8', $playlist),
            ])
            ->assertRedirect(route('tv-channels.index'));

        $this->assertDatabaseHas('tv_channels', ['hotel_id' => Hotel::masterId(), 'tvg_id' => 'direct-one.id', 'name' => 'Direct One']);
        $this->assertDatabaseHas('tv_channels', ['hotel_id' => Hotel::masterId(), 'tvg_id' => 'direct-two.id', 'name' => 'Direct Two']);
    }

    public function test_preview_import_only_stores_the_selected_channels(): void
    {
        $playlist = <<<'M3U'
#EXTM3U
#EXTINF:-1 tvg-id="selected.id",Selected Channel
https://stream.example.test/selected.m3u8
#EXTINF:-1 tvg-id="ignored.id",Ignored Channel
https://stream.example.test/ignored.m3u8
M3U;
        $superadmin = $this->user('superadmin');
        $response = $this->actingAs($superadmin)->post(route('tv-channels.import.preview'), [
            'playlist' => UploadedFile::fake()->createWithContent('selection.m3u8', $playlist),
        ]);
        $token = basename((string) $response->headers->get('Location'));
        $channels = app(M3uPlaylistService::class)->parse($playlist);

        $this->actingAs($superadmin)
            ->post(route('tv-channels.import.store'), [
                'token' => $token,
                'selected' => [$channels[0]['source_hash']],
            ])
            ->assertRedirect(route('tv-channels.index'));

        $this->assertDatabaseHas('tv_channels', ['hotel_id' => Hotel::masterId(), 'tvg_id' => 'selected.id']);
        $this->assertDatabaseMissing('tv_channels', ['hotel_id' => Hotel::masterId(), 'tvg_id' => 'ignored.id']);
    }

    public function test_player_api_returns_only_channels_enabled_for_its_hotel(): void
    {
        $hotel = $this->hotel('TV-API');
        $visible = $this->channel(Hotel::masterId(), 'Visible API Channel');
        $hidden = $this->channel(Hotel::masterId(), 'Hidden API Channel');
        $hotel->tvChannels()->attach($visible->id, [
            'is_active' => true,
            'sort_order' => 1,
            'custom_name' => 'Visible Hotel Channel',
            'custom_type' => 'digital',
            'custom_region' => 'international',
            'custom_stream_url' => 'https://hotel.example.test/live.m3u8',
            'custom_frequency' => 'IPTV',
            'custom_quality' => '4K',
        ]);
        $hotel->tvChannels()->attach($hidden->id, ['is_active' => false, 'sort_order' => 2]);

        $licenseKey = 'TAPI12';
        $hotel->latestLicense()->update([
            'license_key_hash' => Hash::make($licenseKey),
            'license_key_fingerprint' => HotelLicense::fingerprintFor($licenseKey),
        ]);

        $player = new Player([
            'name' => 'API Player',
            'alias' => 'Room 101',
            'serial' => 'API-'.Str::upper(Str::random(8)),
            'token' => 'player-token-'.Str::random(20),
            'token_expires_at' => now()->addDay(),
            'is_active' => true,
        ]);
        $player->hotel_id = $hotel->id;
        $player->save();

        $this->getJson(route('api.player.tv-channels.index'), [
            'X-Hotel-Code' => $hotel->code,
            'X-Hotel-License' => $licenseKey,
            'X-Player-Token' => $player->token,
        ])->assertOk()
            ->assertJsonFragment([
                'name' => 'Visible Hotel Channel',
                'type' => 'digital',
                'region' => 'international',
                'frequency' => 'IPTV',
                'quality' => '4K',
                'stream_type' => 'hls',
                'stream_url' => 'https://hotel.example.test/live.m3u8',
            ])
            ->assertJsonMissing(['name' => 'Hidden API Channel']);
    }

    private function channel(string $hotelId, string $name): TvChannel
    {
        $channel = new TvChannel([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'type' => 'streaming',
            'region' => 'national',
            'stream_url' => 'https://example.test/'.Str::slug($name).'.m3u8',
            'is_active' => true,
        ]);
        $channel->hotel_id = $hotelId;
        $channel->save();

        return $channel;
    }

    private function hotel(string $code): Hotel
    {
        $hotel = Hotel::query()->create([
            'code' => $code.'-'.Str::upper(Str::random(4)),
            'name' => $code,
            'slug' => Str::lower($code).'-'.Str::lower(Str::random(6)),
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id_ID',
            'currency' => 'IDR',
            'status' => 'active',
            'is_active' => true,
        ]);
        HotelLicense::query()->create([
            'hotel_id' => $hotel->id,
            'plan_code' => 'test',
            'status' => HotelLicense::STATUS_ACTIVE,
            'starts_at' => now(),
        ]);

        return $hotel;
    }

    private function user(string $category, ?Hotel $hotel = null): User
    {
        $role = new Role();
        $role->name = 'TV '.$category.' '.Str::random(6);
        $role->category = $category;
        $role->save();

        return User::query()->create([
            'username' => 'tv_'.Str::lower(Str::random(8)),
            'email' => Str::lower(Str::random(8)).'@example.test',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'hotel_id' => $hotel?->id,
            'is_active' => true,
        ]);
    }
}
