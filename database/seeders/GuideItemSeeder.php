<?php

namespace Database\Seeders;

use App\Models\GuideCategory;
use App\Models\GuideItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GuideItemSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure categories exist
        $categoryIds = GuideCategory::pluck('id', 'slug');
        if ($categoryIds->isEmpty()) {
            $this->call(GuideCategorySeeder::class);
            $categoryIds = GuideCategory::pluck('id', 'slug');
        }

        $samples = [
            [
                'title' => 'Lobby Lounge',
                'category' => 'Facilities',
                'short_description' => 'Cozy lounge for quick meetings or waiting area.',
                'description' => 'Located on the ground floor with comfortable seating, Wi‑Fi, and refreshments available on request.',
                'image' => 'images/guides/lobby-lounge.jpg',
                'open_time' => '07:00',
                'close_time' => '22:00',
                'location' => 'Ground Floor, near reception',
                'contact_extension' => '101',
                'sort_order' => 1,
            ],
            [
                'title' => 'Skyview Restaurant',
                'category' => 'Dining',
                'short_description' => 'All-day dining with city skyline views.',
                'description' => 'Buffet breakfast and à la carte lunch/dinner featuring local and international menus.',
                'image' => 'images/guides/skyview-restaurant.jpg',
                'open_time' => '06:30',
                'close_time' => '23:00',
                'location' => 'Level 10',
                'contact_extension' => '555',
                'sort_order' => 2,
            ],
            [
                'title' => 'Infinity Pool',
                'category' => 'Wellness',
                'short_description' => 'Outdoor heated pool with sundeck.',
                'description' => 'Pool towels available on site. Please observe lifeguard instructions and swimwear policy.',
                'image' => 'images/guides/infinity-pool.jpg',
                'open_time' => '07:00',
                'close_time' => '20:00',
                'location' => 'Level 5 Terrace',
                'contact_extension' => '321',
                'sort_order' => 3,
            ],
            [
                'title' => 'City Shuttle',
                'category' => 'Transport',
                'short_description' => 'Complimentary shuttle to key city spots every hour.',
                'description' => 'Reserve seats via concierge. Stops at Central Mall, Museum District, and Train Station.',
                'image' => 'images/guides/city-shuttle.jpg',
                'open_time' => '08:00',
                'close_time' => '20:00',
                'location' => 'Main entrance pick-up',
                'contact_extension' => '9',
                'sort_order' => 4,
            ],
        ];

        $now = now();

        foreach ($samples as $item) {
            $categoryId = $categoryIds[Str::slug($item['category'])] ?? $categoryIds->first();
            $slug = Str::slug($item['title']);
            $storagePath = $item['image'] ?? 'default/no-image.png';
            $ext = pathinfo($storagePath, PATHINFO_EXTENSION) ?: 'png';

            $mediaId = DB::table('medias')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'name' => $item['title'] . ' Image',
                'original_filename' => basename($storagePath),
                'type' => 'image',
                'extension' => strtolower($ext),
                'storage_path' => $storagePath,
                'mime_type' => 'image/' . strtolower($ext),
                'size' => null,
                'duration' => null,
                'width' => null,
                'height' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $payload = [
                'category_id' => $categoryId,
                'title' => $item['title'],
                'slug' => $slug,
                'short_description' => $item['short_description'],
                'description' => $item['description'],
                'image_id' => $mediaId,
                'open_time' => $item['open_time'],
                'close_time' => $item['close_time'],
                'location' => $item['location'],
                'contact_extension' => $item['contact_extension'],
                'sort_order' => $item['sort_order'],
                'is_active' => true,
            ];

            // Handle optional uuid column if present.
            if (Schema::hasColumn('guide_items', 'uuid')) {
                $payload['uuid'] = Str::uuid()->toString();
            }

            GuideItem::updateOrCreate(['slug' => $slug], $payload);
        }
    }
}
