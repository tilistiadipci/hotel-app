<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('login_count')->default(0)->after('last_login_at');
        });

        // Data lama yang sudah memiliki waktu login minimal pernah login sekali.
        DB::table('users')->whereNotNull('last_login_at')->update(['login_count' => 1]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('login_count');
        });
    }
};
