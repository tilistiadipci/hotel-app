<?php

use App\Support\MediaFileCloner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private string $masterMediaRoot = '/master-data';

    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('is_active');
        });

        $masterId = $this->createMasterHotel();
        $defaultThemeId = $this->attachDefaultTheme($masterId);

        $this->migrateSettings($masterId);
        $this->migrateThemeDetails($masterId, $defaultThemeId);
        $this->migrateTvChannels($masterId);

        Schema::dropIfExists('master_tv_channels');
        Schema::dropIfExists('master_theme_details');
        Schema::dropIfExists('master_settings');
    }

    public function down(): void
    {
        $masterId = DB::table('hotels')->where('code', 'MASTER')->value('id');

        if ($masterId) {
            DB::table('medias')->where('hotel_id', $masterId)->delete();
            DB::table('tv_channels')->where('hotel_id', $masterId)->delete();
            DB::table('theme_details')->where('hotel_id', $masterId)->delete();
            DB::table('settings')->where('hotel_id', $masterId)->delete();
            DB::table('hotel_theme')->where('hotel_id', $masterId)->delete();
            DB::table('hotel_licenses')->where('hotel_id', $masterId)->delete();
            DB::table('hotel_configurations')->where('hotel_id', $masterId)->delete();
            DB::table('hotels')->where('id', $masterId)->delete();
        }

        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }

    private function createMasterHotel(): string
    {
        $masterId = (string) Str::uuid();
        $now = now();

        DB::table('hotels')->insert([
            'id' => $masterId,
            'uid' => (string) Str::uuid(),
            'code' => 'MASTER',
            'name' => 'Master Data',
            'slug' => 'master-data',
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id_ID',
            'currency' => 'IDR',
            'status' => 'active',
            'is_active' => true,
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('hotel_configurations')->insert([
            'hotel_id' => $masterId,
            'media_disk' => 'media',
            'media_root' => $this->masterMediaRoot,
            'mqtt_port' => 1883,
            'mqtt_qos' => 0,
            'mqtt_tls' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('hotel_licenses')->insert([
            'id' => (string) Str::uuid(),
            'hotel_id' => $masterId,
            'plan_code' => 'custom',
            'status' => 'active',
            'starts_at' => $now,
            'expires_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $masterId;
    }

    private function attachDefaultTheme(string $masterId): int
    {
        $now = now();

        $defaultThemeId = DB::table('themes')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('id', 1)->orWhere('name', 'Default Theme');
            })
            ->orderByRaw('CASE WHEN id = 1 THEN 0 ELSE 1 END')
            ->value('id');

        // This migration must not assume ThemeSeeder already ran (e.g. a
        // plain `migrate` with no `--seed`) - create a minimal "Default
        // Theme" row ourselves rather than failing on a null theme_id.
        if (! $defaultThemeId) {
            $defaultThemeId = DB::table('themes')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => 'Default Theme',
                'description' => 'Tema default untuk dashboard hotel.',
                'is_default' => '1',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        DB::table('hotel_theme')->insert([
            'hotel_id' => $masterId,
            'theme_id' => $defaultThemeId,
            'is_default' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $defaultThemeId;
    }

    private function migrateSettings(string $masterId): void
    {
        $now = now();

        foreach (DB::table('master_settings')->orderBy('sort_order')->get() as $master) {
            $value = $master->value;

            if ($master->type === 'media' && ! empty($value)) {
                $mediaId = $this->cloneMasterImage($value, $masterId, $master->name);
                $value = $mediaId ? (string) $mediaId : null;
            }

            DB::table('settings')->insert([
                'uuid' => (string) Str::uuid(),
                'hotel_id' => $masterId,
                'key' => $master->key,
                'name' => $master->name,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function migrateThemeDetails(string $masterId, int $themeId): void
    {
        $now = now();

        foreach (DB::table('master_theme_details')->orderBy('sort_order')->get() as $master) {
            $value = $master->value;
            $isImageKey = (bool) preg_match('/^image(_id)?_\d+$/', (string) $master->key);

            if ($isImageKey && ! empty($value)) {
                $paths = json_decode((string) $value, true);
                $paths = is_array($paths) ? $paths : [$value];

                $mediaIds = collect($paths)
                    ->map(fn ($path) => $this->cloneMasterImage($path, $masterId, $master->key))
                    ->filter()
                    ->values();

                $value = match (true) {
                    $mediaIds->isEmpty() => null,
                    $mediaIds->count() === 1 => (string) $mediaIds->first(),
                    default => json_encode($mediaIds->map(fn ($id) => (string) $id)->all(), JSON_UNESCAPED_SLASHES),
                };
            }

            DB::table('theme_details')->insert([
                'uuid' => (string) Str::uuid(),
                'hotel_id' => $masterId,
                'theme_id' => $themeId,
                'key' => $master->key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function migrateTvChannels(string $masterId): void
    {
        $now = now();

        foreach (DB::table('master_tv_channels')->orderBy('sort_order')->get() as $master) {
            $mediaId = $this->cloneMasterImage($master->image_path, $masterId, $master->name.' Logo');

            DB::table('tv_channels')->insert([
                'uuid' => (string) Str::uuid(),
                'hotel_id' => $masterId,
                'name' => $master->name,
                'slug' => $master->slug,
                'type' => $master->type,
                'region' => $master->region,
                'stream_url' => $master->stream_url,
                'frequency' => $master->frequency,
                'quality' => $master->quality,
                'sort_order' => $master->sort_order,
                'is_active' => $master->is_active,
                'image_id' => $mediaId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function cloneMasterImage(?string $masterRelativePath, string $hotelId, string $name): ?int
    {
        if (empty($masterRelativePath)) {
            return null;
        }

        $copied = MediaFileCloner::copyMasterFileToHotel($masterRelativePath, $this->masterMediaRoot);

        if (! $copied) {
            return null;
        }

        return DB::table('medias')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'hotel_id' => $hotelId,
            'name' => $name,
            'original_filename' => $copied['original_filename'],
            'type' => 'image',
            'extension' => $copied['extension'],
            'storage_path' => $copied['relative_path'],
            'mime_type' => $copied['mime_type'],
            'size' => $copied['size'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
