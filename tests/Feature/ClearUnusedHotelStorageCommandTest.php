<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Media;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClearUnusedHotelStorageCommandTest extends TestCase
{
    use DatabaseTransactions;

    private string $mediaBaseRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mediaBaseRoot = storage_path('framework/testing/unused-media-'.Str::uuid());
        File::ensureDirectoryExists($this->mediaBaseRoot);
        config(['filesystems.media_base_root' => $this->mediaBaseRoot]);
    }

    protected function tearDown(): void
    {
        $testingRoot = realpath(storage_path('framework/testing'));
        $target = realpath($this->mediaBaseRoot);

        if ($testingRoot !== false && $target !== false && str_starts_with($target, $testingRoot.DIRECTORY_SEPARATOR)) {
            File::deleteDirectory($target);
        }

        parent::tearDown();
    }

    public function test_it_only_deletes_unused_media_from_the_selected_hotel_folder(): void
    {
        $hotel = Hotel::query()->create([
            'code' => 'CLEAN-'.Str::upper(Str::random(6)),
            'name' => 'Hotel Cleanup Test',
            'slug' => 'hotel-cleanup-'.Str::lower(Str::random(6)),
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id_ID',
            'currency' => 'IDR',
            'status' => 'active',
            'is_active' => true,
        ]);
        $hotel->configuration()->create([
            'media_root' => '/hotel-cleanup-test',
            'mqtt_port' => 1883,
        ]);

        $folder = $this->mediaBaseRoot.DIRECTORY_SEPARATOR.'hotel-cleanup-test';
        File::ensureDirectoryExists($folder.DIRECTORY_SEPARATOR.'images');
        File::ensureDirectoryExists($folder.DIRECTORY_SEPARATOR.'upload-sync');
        File::put($folder.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'used.jpg', 'used');
        File::put($folder.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'unused.jpg', 'unused');
        File::put($folder.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'orphan.jpg', 'orphan');
        File::put($folder.DIRECTORY_SEPARATOR.'upload-sync'.DIRECTORY_SEPARATOR.'pending.jpg', 'pending');

        $used = $this->media($hotel, 'images/used.jpg');
        $unused = $this->media($hotel, 'images/unused.jpg');

        $hotel->setRelation('configuration', $hotel->configuration);
        \App\Models\Setting::query()->create([
            'hotel_id' => $hotel->id,
            'key' => 'general_app_logo',
            'name' => 'General App Logo',
            'value' => (string) $used->id,
        ]);

        $this->artisan('app:clear-storage-unused', [
            '--folderhotelnya' => 'hotel-cleanup-test',
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertFileExists($folder.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'unused.jpg');
        $this->assertDatabaseHas('medias', ['id' => $unused->id]);

        $this->artisan('app:clear-storage-unused', [
            '--folderhotelnya' => '/hotel-cleanup-test',
        ])->assertSuccessful();

        $this->assertDatabaseHas('medias', ['id' => $used->id]);
        $this->assertDatabaseMissing('medias', ['id' => $unused->id]);
        $this->assertFileExists($folder.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'used.jpg');
        $this->assertFileDoesNotExist($folder.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'unused.jpg');
        $this->assertFileDoesNotExist($folder.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'orphan.jpg');
        $this->assertFileExists($folder.DIRECTORY_SEPARATOR.'upload-sync'.DIRECTORY_SEPARATOR.'pending.jpg');
    }

    public function test_it_rejects_an_unregistered_or_unsafe_folder(): void
    {
        $this->artisan('app:clear-storage-unused', ['--folderhotelnya' => '../hotel-lain'])
            ->assertExitCode(2);

        $this->artisan('app:clear-storage-unused', ['--folderhotelnya' => 'tidak-ada'])
            ->assertFailed();
    }

    private function media(Hotel $hotel, string $path): Media
    {
        $media = new Media([
            'name' => pathinfo($path, PATHINFO_FILENAME),
            'original_filename' => basename($path),
            'type' => 'image',
            'extension' => 'jpg',
            'storage_path' => $path,
            'mime_type' => 'image/jpeg',
            'size' => 4,
        ]);
        $media->hotel_id = $hotel->id;
        $media->save();

        return $media;
    }
}
