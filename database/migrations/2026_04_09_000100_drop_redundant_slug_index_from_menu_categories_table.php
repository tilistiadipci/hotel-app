<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasRedundantIndex = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'menu_categories')
            ->where('INDEX_NAME', 'idx_menu_categories_tenant_slug')
            ->exists();

        if (!$hasRedundantIndex || Schema::hasColumn('menu_categories', 'menu_tenant_id')) {
            return;
        }

        Schema::table('menu_categories', function (Blueprint $table) {
            $table->dropIndex('idx_menu_categories_tenant_slug');
        });
    }

    public function down(): void
    {
        // No down migration needed for dropping a redundant index.
    }
};
