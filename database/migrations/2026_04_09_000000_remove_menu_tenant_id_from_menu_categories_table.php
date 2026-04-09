<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('menu_categories', 'menu_tenant_id')) {
            $this->mergeDuplicateCategories();
            $this->dropMenuTenantForeignKey();

            Schema::table('menu_categories', function (Blueprint $table) {
                $table->dropColumn('menu_tenant_id');
            });
        }

        $this->ensureSlugUniqueIndex();
    }

    public function down(): void
    {
        // Irreversible because existing duplicate categories may be merged into one global category.
    }

    private function mergeDuplicateCategories(): void
    {
        $duplicateSlugs = DB::table('menu_categories')
            ->select('slug')
            ->groupBy('slug')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('slug');

        foreach ($duplicateSlugs as $slug) {
            $categories = DB::table('menu_categories')
                ->where('slug', $slug)
                ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
                ->orderBy('id')
                ->get(['id']);

            if ($categories->count() < 2) {
                continue;
            }

            $primaryId = (int) $categories->first()->id;
            $duplicateIds = $categories->slice(1)->pluck('id')->all();

            DB::table('menu_items')
                ->whereIn('category_id', $duplicateIds)
                ->update(['category_id' => $primaryId]);

            if (Schema::hasColumn('menu_transaction_details', 'category_id')) {
                DB::table('menu_transaction_details')
                    ->whereIn('category_id', $duplicateIds)
                    ->update(['category_id' => $primaryId]);
            }

            DB::table('menu_categories')
                ->whereIn('id', $duplicateIds)
                ->delete();
        }
    }

    private function dropMenuTenantForeignKey(): void
    {
        $foreignKeyName = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'menu_categories')
            ->where('COLUMN_NAME', 'menu_tenant_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if (!$foreignKeyName) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `menu_categories` DROP FOREIGN KEY `%s`',
            str_replace('`', '``', $foreignKeyName)
        ));
    }

    private function ensureSlugUniqueIndex(): void
    {
        $hasUniqueIndex = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'menu_categories')
            ->where('COLUMN_NAME', 'slug')
            ->where('NON_UNIQUE', 0)
            ->exists();

        if ($hasUniqueIndex) {
            return;
        }

        Schema::table('menu_categories', function (Blueprint $table) {
            $table->unique('slug');
        });
    }
};
