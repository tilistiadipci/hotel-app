<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            TVChannelSeeder::class,
            MusicSeeder::class,
            // MediaSeeder::class,
            MovieSeeder::class,
            PlaceCategorySeeder::class,
            PlaceSeeder::class,
            MenuCategorySeeder::class,
            MenuItemSeeder::class,
            GuideCategorySeeder::class,
            GuideItemSeeder::class,
            PlayerSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
