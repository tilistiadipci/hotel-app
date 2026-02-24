<?php

namespace Database\Seeders;

use App\Models\PlaceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlaceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Museum',   'slug' => 'museum',   'icon' => 'fa-landmark', 'sort_order' => 1],
            ['name' => 'Park',     'slug' => 'park',     'icon' => 'fa-tree',     'sort_order' => 2],
            ['name' => 'Beach',    'slug' => 'beach',    'icon' => 'fa-umbrella', 'sort_order' => 3],
            ['name' => 'Monument', 'slug' => 'monument', 'icon' => 'fa-archway',  'sort_order' => 4],
            ['name' => 'Mall',     'slug' => 'mall',     'icon' => 'fa-store',    'sort_order' => 5],
        ];

        foreach ($items as $item) {
            PlaceCategory::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'uuid' => Str::uuid()->toString(),
                    'name' => $item['name'],
                    'icon' => $item['icon'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
