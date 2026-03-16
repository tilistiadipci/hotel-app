<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('running_texts', function (Blueprint $table) {
            $table->unsignedBigInteger('running_text_group_id')->nullable()->after('id');
            $table->index('running_text_group_id');
        });

        Schema::table('running_texts', function (Blueprint $table) {
            if (Schema::hasColumn('running_texts', 'link_rss_type')) {
                $table->dropColumn('link_rss_type');
            }
            if (Schema::hasColumn('running_texts', 'link_rss')) {
                $table->dropColumn('link_rss');
            }
        });
    }

    public function down(): void
    {
        Schema::table('running_texts', function (Blueprint $table) {
            if (Schema::hasColumn('running_texts', 'running_text_group_id')) {
                $table->dropIndex(['running_text_group_id']);
                $table->dropColumn('running_text_group_id');
            }
        });

        Schema::table('running_texts', function (Blueprint $table) {
            if (!Schema::hasColumn('running_texts', 'link_rss_type')) {
                $table->string('link_rss_type', 20)->nullable();
            }
            if (!Schema::hasColumn('running_texts', 'link_rss')) {
                $table->text('link_rss')->nullable();
            }
        });
    }
};
