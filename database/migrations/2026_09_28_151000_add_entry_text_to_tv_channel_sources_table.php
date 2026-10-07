<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tv_channel_sources') || Schema::hasColumn('tv_channel_sources', 'entry_text')) {
            return;
        }

        Schema::table('tv_channel_sources', function (Blueprint $table) {
            $table->longText('entry_text')->nullable()->after('stream_url');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tv_channel_sources') || ! Schema::hasColumn('tv_channel_sources', 'entry_text')) {
            return;
        }

        Schema::table('tv_channel_sources', function (Blueprint $table) {
            $table->dropColumn('entry_text');
        });
    }
};
