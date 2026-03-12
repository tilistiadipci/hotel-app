<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
                'price' => 2000,
                'discount_price' => null,
                'description' => 'Grilled bread topped with tomatoes, basil, and olive oil.',
                'is_available' => true,
                'sort_order' => 1,
                'preparation_time' => 10,
            ],
            [
                'name' => 'Caesar Salad',
                'category' => 'appetizers',
                'price' => 3000,
                'discount_price' => 2500,
                'description' => 'Romaine, parmesan, croutons, and house Caesar dressing.',
                'is_available' => true,
                'sort_order' => 2,
                'preparation_time' => 8,
            ],
            [
                'name' => 'Grilled Salmon',
                'category' => 'main-course',
                'price' => 2500,
                'discount_price' => null,
                'description' => 'Norwegian salmon with lemon butter sauce.',
                'is_available' => true,
                'sort_order' => 1,
                'preparation_time' => 18,
            ],
            [
                'name' => 'Ribeye Steak',
                'category' => 'main-course',
                'price' => 2000,
                'discount_price' => 1000,
                'description' => '250gr ribeye, grilled to order, with peppercorn jus.',
                'is_available' => true,
                'sort_order' => 2,
                'preparation_time' => 20,
            ],
            [
                'name' => 'Chocolate Lava Cake',
                'category' => 'desserts',
                'price' => 1500,
                'discount_price' => null,
                'description' => 'Warm chocolate cake with molten center, vanilla ice cream.',
                'is_available' => true,
                'sort_order' => 1,
                'preparation_time' => 12,
            ],
            [
                'name' => 'Iced Latte',
                'category' => 'beverages',
                'price' => 4000,
                'discount_price' => 2000,
                'description' => 'Double espresso with cold milk over ice.',
                'is_available' => true,
                'sort_order' => 1,
                'preparation_time' => 5,
            ],
        ];

        $now = now();

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

            $storagePath = $item['image'] ?? 'default/no-image.png';
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
                    'image_id' => $mediaId,
                ]
            );
        }
    }
}
