<?php

namespace Database\Seeders;

use App\Models\Theme;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $themes = [
            [
                'name' => 'Default Theme',
                'description' => 'Tema default untuk dashboard hotel.',
                'is_default' => '1',
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
                    'created_at' => $existing?->created_at ?? $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
