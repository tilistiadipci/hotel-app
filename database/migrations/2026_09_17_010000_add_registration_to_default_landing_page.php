<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('landing_pages')) {
            return;
        }

        DB::table('landing_pages')
            ->where('html_content', 'like', '%class="lp-wrap"%')
            ->where('html_content', 'not like', '%{{REGISTER_URL}}%')
            ->update([
                'html_content' => file_get_contents(resource_path('landing/default.html')),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // The previous HTML may have been customized, so it is not safe to
        // reconstruct or overwrite it during rollback.
    }
};
