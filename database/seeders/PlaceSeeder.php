<?php

namespace Database\Seeders;

use App\Models\Place;
use App\Models\PlaceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlaceSeeder extends Seeder
{
    public function run(): void
    {
        $categoryId = PlaceCategory::first()?->id;
        if (!$categoryId) {
            $categoryId = PlaceCategory::create([
                'uuid' => Str::uuid()->toString(),
                'name' => 'General',
                'slug' => 'general',
                'is_active' => true,
            ])->id;
        }

        $samples = [
            [
                'name' => 'City History Museum',
                'description' => 'Showcases local heritage and rotating exhibitions.',
                'address' => '123 Heritage St.',
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'distance_km' => 2.5,
                'google_maps_url' => 'https://maps.google.com/?q=City+History+Museum',
            ],
            [
                'name' => 'Central Park',
                'description' => 'Green open space with jogging track and playground.',
                'address' => '456 Green Ave.',
                'latitude' => -6.210000,
                'longitude' => 106.820000,
                'distance_km' => 4.1,
                'google_maps_url' => 'https://maps.google.com/?q=Central+Park',
            ],
        ];

        $now = now();

        foreach ($samples as $item) {
            $storagePath = 'default/no-image.png';
            $ext = pathinfo($storagePath, PATHINFO_EXTENSION) ?: 'png';

            $mediaId = DB::table('medias')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'name' => $item['name'] . ' Image',
                'original_filename' => basename($storagePath),
                'type' => 'image',
                'extension' => $ext,
                'storage_path' => $storagePath,
                'mime_type' => 'image/' . strtolower($ext),
                'size' => null,
                'duration' => null,
                'width' => null,
                'height' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            Place::updateOrCreate(
                ['name' => $item['name']],
                [
                    'uuid' => Str::uuid()->toString(),
                    'category_id' => $categoryId,
                    'is_active' => true,
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'address' => $item['address'],
                    'latitude' => $item['latitude'],
                    'longitude' => $item['longitude'],
                    'distance_km' => $item['distance_km'],
                    'google_maps_url' => $item['google_maps_url'],
                    'image_id' => $mediaId,
                ]
            );
        }
    }
}
