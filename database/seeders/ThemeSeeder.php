<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Theme;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $images = DB::table('medias')->insert([
            [
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
            ]
        ]);

        $media = Media::where('original_filename', 'default-theme.png')->first();

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
    }
}
