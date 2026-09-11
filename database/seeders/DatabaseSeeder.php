<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ThemeSeeder::class,
            ThemeDetailSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            TVChannelSeeder::class,
            MusicSeeder::class,
            // MediaSeeder::class,
            MovieSeeder::class,
            PlaceCategorySeeder::class,
            PlaceSeeder::class,
            MenuTenantSeeder::class,
            MenuCategorySeeder::class,
            MenuItemSeeder::class,
            MenuTransactionDemoSeeder::class,
            GuideCategorySeeder::class,
            GuideItemSeeder::class,
            PlayerSeeder::class,
            SettingSeeder::class,
        ]);

        // Query-builder based legacy seeders bypass Eloquent tenant hooks. Keep
        // them compatible by attaching every seeded business row to the hotel
        // created by the tenancy migration.
        if (Schema::hasTable('hotels')) {
            $hotelId = DB::table('hotels')->orderBy('created_at')->value('id');

            foreach ([
                'medias', 'tv_channels', 'artists', 'places_categories', 'places',
                'menu_categories', 'menu_items', 'albums', 'songs',
                'movies_categories', 'movies',
                'guide_categories', 'guide_items', 'players', 'settings',
                'menu_transactions', 'menu_transaction_details',
                'menu_transaction_invoices', 'themes', 'theme_details', 'bookings',
                'running_texts', 'running_text_groups', 'song_playlists',
                'player_groups', 'warnings', 'menu_tenants', 'audit_reports',
            ] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'hotel_id')) {
                    DB::table($table)->whereNull('hotel_id')->update(['hotel_id' => $hotelId]);
                }
            }

            DB::table('users')
                ->whereNull('hotel_id')
                ->whereIn('role_id', function ($query) {
                    $query->select('id')->from('roles')
                        ->whereNotIn('category', ['master', 'superadmin']);
                })
                ->update(['hotel_id' => $hotelId]);
        }
    }
}
