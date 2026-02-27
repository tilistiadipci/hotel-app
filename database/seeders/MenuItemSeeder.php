<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure categories exist
        $categories = MenuCategory::pluck('id', 'slug')->toArray();
        if (empty($categories)) {
            $this->call(MenuCategorySeeder::class);
            $categories = MenuCategory::pluck('id', 'slug')->toArray();
        }

        $data = [
            [
                'name' => 'Bruschetta',
                'category' => 'appetizers',
                'price' => 45000,
                'discount_price' => null,
                'description' => 'Grilled bread topped with tomatoes, basil, and olive oil.',
                'is_available' => true,
                'sort_order' => 1,
                'preparation_time' => 10,
            ],
            [
                'name' => 'Caesar Salad',
                'category' => 'appetizers',
                'price' => 52000,
                'discount_price' => 48000,
                'description' => 'Romaine, parmesan, croutons, and house Caesar dressing.',
                'is_available' => true,
                'sort_order' => 2,
                'preparation_time' => 8,
            ],
            [
                'name' => 'Grilled Salmon',
                'category' => 'main-course',
                'price' => 145000,
                'discount_price' => null,
                'description' => 'Norwegian salmon with lemon butter sauce.',
                'is_available' => true,
                'sort_order' => 1,
                'preparation_time' => 18,
            ],
            [
                'name' => 'Ribeye Steak',
                'category' => 'main-course',
                'price' => 175000,
                'discount_price' => 160000,
                'description' => '250gr ribeye, grilled to order, with peppercorn jus.',
                'is_available' => true,
                'sort_order' => 2,
                'preparation_time' => 20,
            ],
            [
                'name' => 'Chocolate Lava Cake',
                'category' => 'desserts',
                'price' => 52000,
                'discount_price' => null,
                'description' => 'Warm chocolate cake with molten center, vanilla ice cream.',
                'is_available' => true,
                'sort_order' => 1,
                'preparation_time' => 12,
            ],
            [
                'name' => 'Iced Latte',
                'category' => 'beverages',
                'price' => 38000,
                'discount_price' => 32000,
                'description' => 'Double espresso with cold milk over ice.',
                'is_available' => true,
                'sort_order' => 1,
                'preparation_time' => 5,
            ],
        ];

        foreach ($data as $item) {
            $categorySlug = $item['category'];
            $categoryId = $categories[$categorySlug] ?? null;
            if (!$categoryId) {
                $categoryId = MenuCategory::create([
                    'uuid' => Str::uuid()->toString(),
                    'name' => Str::title(str_replace('-', ' ', $categorySlug)),
                    'slug' => $categorySlug,
                    'is_active' => true,
                ])->id;
                $categories[$categorySlug] = $categoryId;
            }

            MenuItem::updateOrCreate(
                ['name' => $item['name']],
                [
                    'uuid' => Str::uuid()->toString(),
                    'category_id' => $categoryId,
                    'price' => $item['price'],
                    'discount_price' => $item['discount_price'],
                    'description' => $item['description'],
                    'is_available' => $item['is_available'],
                    'sort_order' => $item['sort_order'] ?? 0,
                    'preparation_time' => $item['preparation_time'],
                    'image' => $item['image'] ?? 'default/no-image.png',
                ]
            );
        }
    }
}
