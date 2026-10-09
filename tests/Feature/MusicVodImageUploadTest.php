<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\HotelLicense;
use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use App\Repositories\PlayerMqttRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class MusicVodImageUploadTest extends TestCase
{
    use DatabaseTransactions;

    public function test_music_and_vod_forms_show_the_compact_image_requirement(): void
    {
        [$hotel, $admin] = $this->hotelAdmin();

        $this->actingAs($admin)
            ->get(route('songs.create'))
            ->assertOk()
            ->assertSee('Gambar Musik')
            ->assertSee('Maksimal 300 × 300 piksel dan 300 KB.');

        $this->actingAs($admin)
            ->get(route('movies.create'))
            ->assertOk()
            ->assertSee('Gambar Background VOD')
            ->assertSee('Maksimal 300 × 300 piksel dan 300 KB.');
    }

    public function test_music_vod_picker_accepts_an_image_within_the_limit(): void
    {
        Storage::fake('media');
        [$hotel, $admin] = $this->hotelAdmin();
        $this->fakeMqtt();

        $this->actingAs($admin)
            ->postJson(route('media.store'), [
                'type' => 'image',
                'upload_profile' => 'music_vod',
                'file' => UploadedFile::fake()->image('compact.png', 300, 300)->size(299),
            ])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('media.width', 300)
            ->assertJsonPath('media.height', 300);
    }

    public function test_music_vod_picker_rejects_oversized_dimensions_and_file_size(): void
    {
        Storage::fake('media');
        [$hotel, $admin] = $this->hotelAdmin();
        $this->fakeMqtt();

        $this->actingAs($admin)
            ->postJson(route('media.store'), [
                'type' => 'image',
                'upload_profile' => 'music_vod',
                'file' => UploadedFile::fake()->image('too-wide.png', 301, 300),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->actingAs($admin)
            ->postJson(route('media.store'), [
                'type' => 'image',
                'upload_profile' => 'music_vod',
                'file' => UploadedFile::fake()->image('too-large.png', 300, 300)->size(301),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_compact_limit_does_not_change_regular_media_library_uploads(): void
    {
        Storage::fake('media');
        [$hotel, $admin] = $this->hotelAdmin();
        $this->fakeMqtt();

        $this->actingAs($admin)
            ->postJson(route('media.store'), [
                'type' => 'image',
                'file' => UploadedFile::fake()->image('regular-media.png', 800, 600)->size(500),
            ])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('media.width', 800)
            ->assertJsonPath('media.height', 600);
    }

    public function test_music_vod_picker_still_lists_existing_hotel_images_without_dimension_metadata(): void
    {
        [$hotel, $admin] = $this->hotelAdmin();
        $media = new Media([
            'name' => 'Legacy BIO Image',
            'original_filename' => 'legacy-bio.png',
            'type' => 'image',
            'extension' => 'png',
            'storage_path' => 'images/legacy-bio.png',
            'size' => 14000,
            'width' => null,
            'height' => null,
        ]);
        $media->hotel_id = $hotel->id;
        $media->save();

        $this->actingAs($admin)
            ->getJson(route('media.library', [
                'type' => 'image',
                'upload_profile' => 'music_vod',
            ]))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $media->id,
                'name' => 'Legacy BIO Image',
            ]);
    }

    private function hotelAdmin(): array
    {
        $code = 'IMG-'.Str::upper(Str::random(6));
        $hotel = Hotel::query()->create([
            'code' => $code,
            'name' => $code,
            'slug' => Str::lower($code).'-'.Str::lower(Str::random(5)),
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

        $role = new Role();
        $role->name = 'Image Admin '.Str::random(6);
        $role->category = 'admin';
        $role->save();

        $admin = User::query()->create([
            'hotel_id' => $hotel->id,
            'username' => 'image_'.Str::lower(Str::random(8)),
            'email' => Str::lower(Str::random(8)).'@example.test',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        return [$hotel, $admin];
    }

    private function fakeMqtt(): void
    {
        $mqtt = Mockery::mock(PlayerMqttRepository::class);
        $mqtt->shouldReceive('publishHotelUpdate')->zeroOrMoreTimes();
        $this->app->instance(PlayerMqttRepository::class, $mqtt);
    }
}
