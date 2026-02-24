<?php

namespace Database\Seeders;

use App\Models\Place;
use App\Models\PlaceCategory;
use Illuminate\Database\Seeder;
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
                'image' => 'images/places/museum.jpg',
                'address' => '123 Heritage St.',
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'distance_km' => 2.5,
                'google_maps_url' => 'https://maps.google.com/?q=City+History+Museum',
            ],
            [
                'name' => 'Central Park',
                'description' => 'Green open space with jogging track and playground.',
                'image' => 'images/places/park.jpg',
                'address' => '456 Green Ave.',
                'latitude' => -6.210000,
                'longitude' => 106.820000,
                'distance_km' => 4.1,
                'google_maps_url' => 'https://maps.google.com/?q=Central+Park',
            ],
        ];

        foreach ($samples as $item) {
            Place::updateOrCreate(
                ['name' => $item['name']],
                array_merge($item, [
                    'uuid' => Str::uuid()->toString(),
                    'category_id' => $categoryId,
                    'is_active' => true,
                ])
            );
        }
    }
}
