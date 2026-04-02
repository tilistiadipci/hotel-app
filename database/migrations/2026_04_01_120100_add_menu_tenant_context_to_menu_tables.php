<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_categories', function (Blueprint $table) {
            $table->dropUnique('menu_categories_slug_unique');
            $table->foreignId('menu_tenant_id')
                ->nullable()
                ->after('uuid')
                ->constrained('menu_tenants')
                ->restrictOnDelete();
            $table->index(['menu_tenant_id', 'slug'], 'idx_menu_categories_tenant_slug');
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->foreignId('menu_tenant_id')
                ->nullable()
                ->after('uuid')
                ->constrained('menu_tenants')
                ->restrictOnDelete();
            $table->index('menu_tenant_id', 'idx_menu_items_tenant');
        });

        Schema::table('menu_transactions', function (Blueprint $table) {
            $table->foreignId('menu_tenant_id')
                ->nullable()
                ->after('uuid')
                ->constrained('menu_tenants')
                ->restrictOnDelete();
            $table->index('menu_tenant_id', 'idx_menu_transactions_tenant');
        });

        Schema::table('menu_transaction_details', function (Blueprint $table) {
            $table->foreignId('menu_tenant_id')
                ->nullable()
                ->after('menu_transaction_id')
                ->constrained('menu_tenants')
                ->restrictOnDelete();
            $table->foreignId('category_id')
                ->nullable()
                ->after('menu_tenant_id')
                ->constrained('menu_categories')
                ->nullOnDelete();
            $table->index('menu_tenant_id', 'idx_menu_transaction_details_tenant');
            $table->index('category_id', 'idx_menu_transaction_details_category');
        });

        Schema::table('menu_transaction_invoices', function (Blueprint $table) {
            $table->foreignId('menu_tenant_id')
                ->nullable()
                ->after('uuid')
                ->constrained('menu_tenants')
                ->restrictOnDelete();
            $table->index('menu_tenant_id', 'idx_menu_transaction_invoices_tenant');
        });

        $tenantId = DB::table('menu_tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Main Pantry',
            'slug' => 'main-pantry',
            'description' => 'Default tenant for existing shopping data.',
            'location' => null,
            'sort_order' => 0,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('menu_categories')
            ->whereNull('menu_tenant_id')
            ->update(['menu_tenant_id' => $tenantId]);

        DB::table('menu_items')
            ->join('menu_categories', 'menu_categories.id', '=', 'menu_items.category_id')
            ->whereNull('menu_items.menu_tenant_id')
            ->update(['menu_items.menu_tenant_id' => DB::raw('menu_categories.menu_tenant_id')]);

        DB::table('menu_transactions')
            ->whereNull('menu_tenant_id')
            ->update(['menu_tenant_id' => $tenantId]);

        DB::table('menu_transaction_details')
            ->join('menu_items', 'menu_items.id', '=', 'menu_transaction_details.menu_id')
            ->whereNull('menu_transaction_details.menu_tenant_id')
            ->update([
                'menu_transaction_details.menu_tenant_id' => DB::raw('menu_items.menu_tenant_id'),
                'menu_transaction_details.category_id' => DB::raw('menu_items.category_id'),
            ]);

        DB::table('menu_transaction_details')
            ->whereNull('menu_tenant_id')
            ->update(['menu_tenant_id' => $tenantId]);

        DB::table('menu_transaction_invoices')
            ->join('menu_transactions', 'menu_transactions.id', '=', 'menu_transaction_invoices.menu_transaction_id')
            ->whereNull('menu_transaction_invoices.menu_tenant_id')
            ->update(['menu_transaction_invoices.menu_tenant_id' => DB::raw('menu_transactions.menu_tenant_id')]);
    }

    public function down(): void
    {
        Schema::table('menu_transaction_invoices', function (Blueprint $table) {
            $table->dropIndex('idx_menu_transaction_invoices_tenant');
            $table->dropConstrainedForeignId('menu_tenant_id');
        });

        Schema::table('menu_transaction_details', function (Blueprint $table) {
            $table->dropIndex('idx_menu_transaction_details_tenant');
            $table->dropIndex('idx_menu_transaction_details_category');
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('menu_tenant_id');
        });

        Schema::table('menu_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_menu_transactions_tenant');
            $table->dropConstrainedForeignId('menu_tenant_id');
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropIndex('idx_menu_items_tenant');
            $table->dropConstrainedForeignId('menu_tenant_id');
        });

        Schema::table('menu_categories', function (Blueprint $table) {
            $table->dropIndex('idx_menu_categories_tenant_slug');
            $table->dropConstrainedForeignId('menu_tenant_id');
            $table->unique('slug');
        });
    }
};
