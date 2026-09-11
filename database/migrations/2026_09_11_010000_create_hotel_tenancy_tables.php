<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Tables whose rows are owned directly by a hotel.
     * Pure pivot tables inherit the hotel through their parent records.
     */
    private array $tenantTables = [
        'medias',
        'tv_channels',
        'artists',
        'places_categories',
        'places',
        'menu_categories',
        'menu_items',
        'albums',
        'songs',
        'movies_categories',
        'movies',
        'guide_categories',
        'guide_items',
        'players',
        'settings',
        'menu_transactions',
        'menu_transaction_details',
        'menu_transaction_invoices',
        'themes',
        'theme_details',
        'bookings',
        'running_texts',
        'running_text_groups',
        'song_playlists',
        'player_groups',
        'warnings',
        'menu_tenants',
        'audit_reports',
    ];

    private array $tenantUniqueColumns = [
        'tv_channels' => 'slug',
        'places_categories' => 'slug',
        'menu_categories' => 'slug',
        'movies_categories' => 'slug',
        'guide_categories' => 'slug',
        'guide_items' => 'slug',
        'settings' => 'key',
        'menu_transaction_invoices' => 'invoice_number',
        'menu_tenants' => 'slug',
    ];

    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('uid')->unique();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('slug', 170)->unique();
            $table->string('timezone', 50)->default('Asia/Jakarta');
            $table->string('locale', 10)->default('id_ID');
            $table->string('currency', 3)->default('IDR');
            $table->string('status', 30)->default('active')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hotel_licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hotel_id');
            $table->string('license_key_hash')->nullable()->unique();
            $table->string('plan_code', 50)->default('custom');
            $table->string('status', 30)->default('trial')->index();
            $table->unsignedInteger('max_players')->nullable();
            $table->unsignedInteger('max_users')->nullable();
            $table->json('features')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('grace_ends_at')->nullable();
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
            $table->index(['hotel_id', 'status', 'expires_at']);
        });

        $defaultHotelId = (string) Str::uuid();
        $now = now();

        DB::table('hotels')->insert([
            'id' => $defaultHotelId,
            'uid' => (string) Str::uuid(),
            'code' => 'BIO-HOTEL',
            'name' => 'Bio Experience Hotel',
            'slug' => 'bio-experience-hotel',
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id_ID',
            'currency' => 'IDR',
            'status' => 'active',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('hotel_licenses')->insert([
            'id' => (string) Str::uuid(),
            'hotel_id' => $defaultHotelId,
            'plan_code' => 'legacy-custom',
            'status' => 'active',
            'features' => json_encode(['all' => true]),
            'starts_at' => $now,
            'expires_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('hotel_id')->nullable()->after('id')->index();
            $table->foreign('hotel_id')->references('id')->on('hotels')->nullOnDelete();
        });

        // Platform master/superadmin remain global. Existing hotel staff are
        // attached to the default hotel so the current installation keeps working.
        DB::table('users')
            ->whereIn('role_id', function ($query) {
                $query->select('id')->from('roles')->whereNotIn('category', ['master', 'superadmin']);
            })
            ->update(['hotel_id' => $defaultHotelId]);

        foreach ($this->tenantTables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'hotel_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->uuid('hotel_id')->nullable()->index();
                $table->foreign('hotel_id', $tableName.'_hotel_id_foreign')
                    ->references('id')->on('hotels')->nullOnDelete();
            });

            DB::table($tableName)->whereNull('hotel_id')->update(['hotel_id' => $defaultHotelId]);
        }

        // Values such as setting keys and slugs only need to be unique inside
        // one hotel. Public UUIDs and hardware player serials remain global.
        foreach ($this->tenantUniqueColumns as $tableName => $column) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'hotel_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($column) {
                $table->dropUnique([$column]);
                $table->unique(['hotel_id', $column]);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tenantUniqueColumns as $tableName => $column) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'hotel_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($column) {
                $table->dropUnique(['hotel_id', $column]);
                $table->unique($column);
            });
        }

        foreach (array_reverse($this->tenantTables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'hotel_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropForeign($tableName.'_hotel_id_foreign');
                $table->dropIndex(['hotel_id']);
                $table->dropColumn('hotel_id');
            });
        }

        if (Schema::hasColumn('users', 'hotel_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['hotel_id']);
                $table->dropIndex(['hotel_id']);
                $table->dropColumn('hotel_id');
            });
        }

        Schema::dropIfExists('hotel_licenses');
        Schema::dropIfExists('hotels');
    }
};
