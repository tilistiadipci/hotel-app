<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use App\Models\Media;
use App\Models\MenuItem;
use App\Models\MenuTenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = MenuTenant::withTrashed()->firstOrNew([
            'slug' => 'main-pantry',
        ]);

        if (!$tenant->exists) {
            $tenant->uuid = Str::uuid()->toString();
        }

        $tenant->name = 'Main Pantry';
        $tenant->description = 'Default tenant for shopping items.';
        $tenant->sort_order = 1;
        $tenant->is_active = true;
        $tenant->deleted_at = null;
        $tenant->deleted_by = null;
        $tenant->save();

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
                $category = MenuCategory::withTrashed()->firstOrNew([
                    'slug' => $categorySlug,
                ]);

                if (!$category->exists) {
                    $category->uuid = Str::uuid()->toString();
                }

                $category->name = Str::title(str_replace('-', ' ', $categorySlug));
                $category->is_active = true;
                $category->deleted_at = null;
                $category->deleted_by = null;
                $category->save();

                $categoryId = $category->id;
                $categories[$categorySlug] = $categoryId;
            }

            $storagePath = $item['image'] ?? 'default/no-image.png';
            $ext = pathinfo($storagePath, PATHINFO_EXTENSION) ?: 'png';
            $mediaName = Str::title(str_replace(['-', '_'], ' ', pathinfo($storagePath, PATHINFO_FILENAME)));

            $media = Media::withTrashed()->firstOrNew([
                'type' => 'image',
                'storage_path' => $storagePath,
            ]);

            if (!$media->exists) {
                $media->uuid = Str::uuid()->toString();
            }

            $media->name = $mediaName;
            $media->original_filename = basename($storagePath);
            $media->extension = $ext;
            $media->mime_type = 'image/' . strtolower($ext);
            $media->size = null;
            $media->duration = null;
            $media->width = null;
            $media->height = null;
            $media->deleted_at = null;
            $media->deleted_by = null;
            $media->save();

            $menuItem = MenuItem::withTrashed()->firstOrNew([
                'menu_tenant_id' => $tenant->id,
                'name' => $item['name'],
            ]);

            if (!$menuItem->exists) {
                $menuItem->uuid = Str::uuid()->toString();
            }

            $menuItem->menu_tenant_id = $tenant->id;
            $menuItem->category_id = $categoryId;
            $menuItem->price = $item['price'];
            $menuItem->discount_price = $item['discount_price'];
            $menuItem->description = $item['description'];
            $menuItem->is_available = $item['is_available'];
            $menuItem->sort_order = $item['sort_order'] ?? 0;
            $menuItem->preparation_time = $item['preparation_time'];
            $menuItem->image_id = $media->id;
            $menuItem->deleted_at = null;
            $menuItem->deleted_by = null;
            $menuItem->save();
        }
    }
}
