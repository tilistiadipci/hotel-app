<?php

namespace Database\Seeders;

use App\Tenancy\TenantContext;
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
        $hotelId = Schema::hasTable('hotels')
            ? DB::table('hotels')->where('is_system', false)->whereNull('deleted_at')->orderBy('created_at')->value('id')
            : null;

        app(TenantContext::class)->set($hotelId);

        if ($hotelId && Schema::hasColumn('hotels', 'address')) {
            DB::table('hotels')->where('id', $hotelId)->whereNull('address')->update([
                'address' => 'Jl. Jalan Raya No. 123',
            ]);
        }

        try {
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
        } finally {
            app(TenantContext::class)->clear();
        }

        // Query-builder based legacy seeders bypass Eloquent tenant hooks. Keep
        // them compatible by attaching every seeded business row to the hotel
        // created by the tenancy migration.
        if (Schema::hasTable('hotels')) {
            foreach ([
                'medias', 'artists', 'places_categories', 'places',
                'menu_categories', 'menu_items', 'albums', 'songs',
                'movies_categories', 'movies',
                'guide_categories', 'guide_items', 'players',
                'menu_transactions', 'menu_transaction_details',
                'menu_transaction_invoices', 'bookings',
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
                        ->whereNotIn('category', ['master', 'superadmin', 'manager']);
                })
                ->update(['hotel_id' => $hotelId]);
        }
    }
}
