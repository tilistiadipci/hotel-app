<?php

namespace Database\Seeders;

use App\Models\GuideCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GuideCategorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Facilities', 'sort_order' => 1],
            ['name' => 'Dining', 'sort_order' => 2],
            ['name' => 'Wellness', 'sort_order' => 3],
            ['name' => 'Activities', 'sort_order' => 4],
            ['name' => 'Transport', 'sort_order' => 5],
        ];

        foreach ($items as $item) {
            $slug = Str::slug($item['name']);
            $payload = [
                'name' => $item['name'],
                'slug' => $slug,
                'sort_order' => $item['sort_order'],
                'is_active' => true,
            ];

            // Some environments might have a non-nullable uuid column.
            if (Schema::hasColumn('guide_categories', 'uuid')) {
                $payload['uuid'] = Str::uuid()->toString();
            }

            GuideCategory::updateOrCreate(['slug' => $slug], $payload);
        }
    }
}
