<?php

use App\Support\MediaFileCloner;
use App\Tenancy\HotelMediaPath;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTables();
        $this->rescopeThemeDetailsUnique();

        $now = now();
        $this->seedMasterSettings($now);
        $this->seedMasterThemeDetails($now);
        $this->seedMasterTvChannels($now);

        $this->backfillHotels($now);
    }

    public function down(): void
    {
        Schema::table('theme_details', function (Blueprint $table) {
            $table->dropUnique('theme_details_hotel_theme_key_unique');
            $table->unique(['theme_id', 'key']);
        });

        Schema::dropIfExists('master_tv_channels');
        Schema::dropIfExists('master_theme_details');
        Schema::dropIfExists('master_settings');
    }

    private function createTables(): void
    {
        Schema::create('master_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('group_key', 50);
            $table->string('key', 150)->unique();
            $table->string('name', 150);
            $table->string('type', 20);
            $table->text('value')->nullable();
            $table->json('options')->nullable();
            $table->unsignedSmallInteger('max_length')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('master_theme_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('key', 200)->unique();
            $table->text('value')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('master_tv_channels', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            $table->enum('type', ['digital', 'streaming']);
            $table->enum('region', ['national', 'international']);
            $table->string('stream_url', 255)->nullable();
            $table->string('frequency', 100)->nullable();
            $table->string('quality', 20)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    private function rescopeThemeDetailsUnique(): void
    {
        Schema::table('theme_details', function (Blueprint $table) {
            $table->dropUnique(['theme_id', 'key']);
            $table->unique(['hotel_id', 'theme_id', 'key'], 'theme_details_hotel_theme_key_unique');
        });
    }

    private function statusOptions(): array
    {
        return ['active' => 'Active', 'inactive' => 'Inactive'];
    }

    private function seedMasterSettings($now): void
    {
        $status = $this->statusOptions();

        $fields = [
            // group, key, name, type, default, options, max
            ['general', 'default_language', 'Default Language', 'select', 'id_ID', ['id_ID' => 'Bahasa Indonesia', 'en_US' => 'English (US)'], null],
            ['general', 'general_app_name', 'General App Name', 'text', '', null, 150],
            ['general', 'api_key_status', 'API Key Status', 'select', 'active', $status, null],
            ['general', 'longitude_app', 'Longitude', 'number', '0', null, null],
            ['general', 'latitude_app', 'Latitude', 'number', '0', null, null],
            ['general', 'general_app_logo', 'General App Logo', 'media', 'default/theme-1.png', null, null],
            ['general', 'general_app_logo2', 'General App Logo 2', 'media', 'default/theme-1.png', null, null],

            ['menus', 'menu_home_label', 'Menu Home Label', 'text', 'Home', null, 80],
            ['menus', 'menu_live_tv_label', 'Menu Live TV Label', 'text', 'Live TV', null, 80],
            ['menus', 'menu_live_tv_status', 'Menu Live TV Status', 'select', 'active', $status, null],
            ['menus', 'menu_streaming_tv_label', 'Menu Streaming TV Label', 'text', 'Streaming TV', null, 80],
            ['menus', 'menu_streaming_tv_status', 'Menu Streaming TV Status', 'select', 'active', $status, null],
            ['menus', 'menu_music_label', 'Menu Music Label', 'text', 'Music', null, 80],
            ['menus', 'menu_music_status', 'Menu Music Status', 'select', 'active', $status, null],
            ['menus', 'menu_vod_label', 'Menu VOD Label', 'text', 'VOD', null, 80],
            ['menus', 'menu_vod_status', 'Menu VOD Status', 'select', 'active', $status, null],
            ['menus', 'menu_guide_label', 'Menu Guide Label', 'text', 'Guide', null, 80],
            ['menus', 'menu_guide_status', 'Menu Guide Status', 'select', 'active', $status, null],
            ['menus', 'menu_nearby_label', 'Menu Nearby Label', 'text', 'Nearby', null, 80],
            ['menus', 'menu_nearby_status', 'Menu Nearby Status', 'select', 'active', $status, null],
            ['menus', 'menu_shopping_label', 'Menu Shopping Label', 'text', 'Shopping', null, 80],
            ['menus', 'menu_shopping_status', 'Menu Shopping Status', 'select', 'active', $status, null],

            ['apps', 'customize_menu_active', 'Customize Menu Other Active', 'select', 'inactive', $status, null],
            ['apps', 'other_apps_netflix', 'Netflix', 'select', 'inactive', $status, null],
            ['apps', 'other_apps_vidio', 'Vidio', 'select', 'inactive', $status, null],
            ['apps', 'other_apps_disney', 'Disney+', 'select', 'inactive', $status, null],
            ['apps', 'other_apps_wetv', 'WeTV', 'select', 'inactive', $status, null],
            ['apps', 'other_apps_prime', 'Prime Video', 'select', 'inactive', $status, null],
            ['apps', 'other_apps_youtube', 'YouTube', 'select', 'inactive', $status, null],

            ['mobile', 'mobile_menu_music', 'Mobile Menu Music', 'select', 'active', $status, null],
            ['mobile', 'mobile_menu_vod', 'Mobile Menu VOD', 'select', 'active', $status, null],
            ['mobile', 'mobile_menu_guide', 'Mobile Menu Guide', 'select', 'active', $status, null],
            ['mobile', 'mobile_menu_nearby', 'Mobile Menu Nearby', 'select', 'active', $status, null],
            ['mobile', 'mobile_menu_shopping', 'Mobile Menu Shopping', 'select', 'active', $status, null],
            ['mobile', 'mobile_menu_other_page_website', 'Mobile Menu Other Page Website', 'select', 'active', $status, null],

            ['transaction', 'tax_percentage_grand_total_status', 'Tax Percentage Grand Total Status', 'select', 'inactive', $status, null],
            ['transaction', 'tax_percentage_grand_total', 'Tax Percentage Grand Total (%)', 'number', '0', null, null],
            ['transaction', 'service_charge_status', 'Service Charge Status', 'select', 'inactive', $status, null],
            ['transaction', 'service_charge_fixed', 'Service Charge (Fixed)', 'number', '0', null, null],
            ['transaction', 'alert_notification', 'Alert Notification', 'select', 'inactive', $status, null],
            ['transaction', 'warning_broadcast_status', 'Warning Broadcast Status', 'select', 'active', $status, null],

            ['contact', 'about_phone', 'About Phone Number', 'text', '', null, 50],
            ['contact', 'about_email', 'About Email', 'email', '', null, 150],
            ['contact', 'about_website', 'About Website', 'url', '', null, 255],
            ['contact', 'about_ssid', 'About SSID', 'text', '', null, 100],
            ['contact', 'about_wifi_password', 'About WIFI Password', 'text', '', null, 150],
        ];

        foreach ($fields as $sort => [$group, $key, $name, $type, $default, $options, $max]) {
            DB::table('master_settings')->insert([
                'uuid' => (string) Str::uuid(),
                'group_key' => $group,
                'key' => $key,
                'name' => $name,
                'type' => $type,
                'value' => $default,
                'options' => $options ? json_encode($options) : null,
                'max_length' => $max,
                'sort_order' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedMasterThemeDetails($now): void
    {
        $rows = [
            ['header_show_date', '1'],
            ['header_show_title', '1'],
            ['header_show_room_name', '1'],
            ['header_scale', '1'],
            ['footer_scale', '1'],
            ['font_scale', '1'],
            ['image_id_1', json_encode(['default/theme-1.png', 'images/theme-gallery-1.png', 'images/theme-gallery-2.jpg'], JSON_UNESCAPED_SLASHES)],
            ['image_id_2', 'default/no-image.png'],
            ['image_id_3', 'images/theme-banner.jpg'],
        ];

        foreach ($rows as $sort => [$key, $value]) {
            DB::table('master_theme_details')->insert([
                'uuid' => (string) Str::uuid(),
                'key' => $key,
                'value' => $value,
                'sort_order' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedMasterTvChannels($now): void
    {
        $rows = [
            ['SCTV', 'sctv', 'digital', 'national', null, 'UHF 24', 'HD', 1],
            ['TVRI', 'tvri', 'digital', 'national', null, 'UHF 43', 'HD', 2],
            ['Netflix', 'netflix', 'streaming', 'international', 'netflix.com', null, 'HD', 10],
            ['Disney+', 'disney-plus', 'streaming', 'international', 'disneyplus.com', null, 'HD', 11],
            ['Vidio', 'vidio', 'streaming', 'national', 'vidio.com', null, 'HD', 12],
        ];

        foreach ($rows as [$name, $slug, $type, $region, $streamUrl, $frequency, $quality, $sort]) {
            DB::table('master_tv_channels')->insert([
                'uuid' => (string) Str::uuid(),
                'name' => $name,
                'slug' => $slug,
                'type' => $type,
                'region' => $region,
                'stream_url' => $streamUrl,
                'frequency' => $frequency,
                'quality' => $quality,
                'image_path' => 'default/no-image.png',
                'sort_order' => $sort,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function backfillHotels($now): void
    {
        $masterSettings = DB::table('master_settings')->get();
        $masterThemeDetails = DB::table('master_theme_details')->get();
        $masterTvChannels = DB::table('master_tv_channels')->get();

        $hotels = DB::table('hotels')->whereNull('deleted_at')->get(['id']);

        foreach ($hotels as $hotel) {
            $mediaRoot = DB::table('hotel_configurations')->where('hotel_id', $hotel->id)->value('media_root');

            if (! $mediaRoot) {
                continue;
            }

            $this->backfillSettings($hotel->id, $mediaRoot, $masterSettings, $now);
            $this->backfillThemeDetails($hotel->id, $mediaRoot, $masterThemeDetails, $now);
            $this->backfillTvChannels($hotel->id, $mediaRoot, $masterTvChannels, $now);
        }
    }

    private function backfillSettings(string $hotelId, string $mediaRoot, $masterSettings, $now): void
    {
        $existingKeys = DB::table('settings')->where('hotel_id', $hotelId)->pluck('key')->all();

        foreach ($masterSettings as $master) {
            if (in_array($master->key, $existingKeys, true)) {
                continue;
            }

            $value = $master->value;

            if ($master->type === 'media' && ! empty($value)) {
                $mediaId = $this->cloneMasterImage($value, $hotelId, $mediaRoot, $master->name);
                $value = $mediaId ? (string) $mediaId : null;
            }

            DB::table('settings')->insert([
                'uuid' => (string) Str::uuid(),
                'hotel_id' => $hotelId,
                'key' => $master->key,
                'name' => $master->name,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function backfillThemeDetails(string $hotelId, string $mediaRoot, $masterThemeDetails, $now): void
    {
        $defaultThemeId = DB::table('hotel_theme')
            ->where('hotel_id', $hotelId)
            ->where('is_default', true)
            ->value('theme_id');

        if (! $defaultThemeId) {
            return;
        }

        $existingKeys = DB::table('theme_details')
            ->where('hotel_id', $hotelId)
            ->where('theme_id', $defaultThemeId)
            ->pluck('key')->all();

        foreach ($masterThemeDetails as $master) {
            if (in_array($master->key, $existingKeys, true)) {
                continue;
            }

            $value = $master->value;
            $isImageKey = (bool) preg_match('/^image(_id)?_\d+$/', (string) $master->key);

            if ($isImageKey && ! empty($value)) {
                $paths = json_decode((string) $value, true);
                $paths = is_array($paths) ? $paths : [$value];

                $mediaIds = collect($paths)
                    ->map(fn ($path) => $this->cloneMasterImage($path, $hotelId, $mediaRoot, $master->key))
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
                'hotel_id' => $hotelId,
                'theme_id' => $defaultThemeId,
                'key' => $master->key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function backfillTvChannels(string $hotelId, string $mediaRoot, $masterTvChannels, $now): void
    {
        $hasChannels = DB::table('tv_channels')->where('hotel_id', $hotelId)->exists();

        if ($hasChannels) {
            return;
        }

        foreach ($masterTvChannels as $master) {
            $mediaId = $this->cloneMasterImage($master->image_path, $hotelId, $mediaRoot, $master->name.' Logo');

            DB::table('tv_channels')->insert([
                'uuid' => (string) Str::uuid(),
                'hotel_id' => $hotelId,
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

    private function cloneMasterImage(?string $masterRelativePath, string $hotelId, string $mediaRoot, string $name): ?int
    {
        if (empty($masterRelativePath)) {
            return null;
        }

        $copied = MediaFileCloner::copyMasterFileToHotel($masterRelativePath, $mediaRoot);

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
