<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('running_texts', function (Blueprint $table) {
            $table->string('link_rss_type', 20)->nullable()->after('description');
            $table->text('link_rss')->nullable()->after('link_rss_type');
        });
    }

    public function down(): void
    {
        Schema::table('running_texts', function (Blueprint $table) {
            $table->dropColumn(['link_rss_type', 'link_rss']);
        });
    }
};
