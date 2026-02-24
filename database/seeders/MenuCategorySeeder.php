<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuCategorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Appetizers', 'sort_order' => 1],
            ['name' => 'Main Course', 'sort_order' => 2],
            ['name' => 'Desserts', 'sort_order' => 3],
            ['name' => 'Beverages', 'sort_order' => 4],
            ['name' => 'Specials', 'sort_order' => 5],
            ['name' => 'Vegan', 'sort_order' => 6],
            ['name' => 'Kids Menu', 'sort_order' => 7],
        ];

        foreach ($items as $item) {
            $slug = Str::slug($item['name']);
            MenuCategory::updateOrCreate(
                ['slug' => $slug],
                [
                    'uuid' => Str::uuid()->toString(),
                    'name' => $item['name'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                    'description' => null,
                ]
            );
        }
    }
}
