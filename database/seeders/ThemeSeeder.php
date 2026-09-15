<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Theme;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $media = Media::query()->withoutGlobalScope('hotel')
            ->where('original_filename', 'default-theme.png')
            ->first();

        if (! $media) {
            $mediaId = DB::table('medias')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => 'Default Theme',
                'original_filename' => 'default-theme.png',
                'type' => 'image',
                'extension' => 'png',
                'storage_path' => 'default/theme-1.png',
                'mime_type' => 'image/png',
                'size' => null,
                'duration' => null,
                'width' => null,
                'height' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $media = Media::query()->withoutGlobalScope('hotel')->find($mediaId);
        }

        $themes = [
            [
                'name' => 'Default Theme',
                'description' => 'Tema default untuk dashboard hotel.',
                'is_default' => '1',
                'image_id' => $media->id,
            ],
            [
                'name' => 'Executive Theme',
                'description' => 'Tema alternatif dengan nuansa lebih gelap dan formal.',
                'is_default' => '0',
            ],
        ];

        foreach ($themes as $theme) {
            $existing = Theme::query()->where('name', $theme['name'])->first();

            Theme::query()->updateOrCreate(
                ['name' => $theme['name']],
                [
                    'uuid' => $existing?->uuid ?? Str::uuid()->toString(),
                    'description' => $theme['description'],
                    'is_default' => $theme['is_default'],
                    'image_id' => $theme['image_id'] ?? null,
                    'created_at' => $existing?->created_at ?? $now,
                    'updated_at' => $now,
                ]
            );
        }

        if (! Schema::hasTable('hotel_theme')) {
            return;
        }

        $primaryHotelId = DB::table('hotels')
            ->where('is_system', false)
            ->whereNull('deleted_at')
            ->orderBy('created_at')
            ->value('id');
        $defaultTheme = Theme::query()->where('name', 'Default Theme')->first();

        foreach (Theme::query()->whereNull('deleted_at')->get() as $theme) {
            if ($primaryHotelId && ! DB::table('hotel_theme')
                ->where('hotel_id', $primaryHotelId)
                ->where('theme_id', $theme->id)
                ->exists()) {
                DB::table('hotel_theme')->insert([
                    'hotel_id' => $primaryHotelId,
                    'theme_id' => $theme->id,
                    'is_default' => ! DB::table('hotel_theme')->where('hotel_id', $primaryHotelId)->where('is_default', true)->exists(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if ($defaultTheme) {
            foreach (DB::table('hotels')->whereNull('deleted_at')->pluck('id') as $hotelId) {
                if (DB::table('hotel_theme')->where('hotel_id', $hotelId)->where('theme_id', $defaultTheme->id)->exists()) {
                    continue;
                }

                DB::table('hotel_theme')->insert([
                    'hotel_id' => $hotelId,
                    'theme_id' => $defaultTheme->id,
                    'is_default' => ! DB::table('hotel_theme')->where('hotel_id', $hotelId)->where('is_default', true)->exists(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
