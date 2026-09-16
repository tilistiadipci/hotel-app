<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration', function (Blueprint $table): void {
            $table->string('manager_address', 255)->nullable()->after('admin_address');
            $table->foreignId('manager_user_id')->nullable()->after('admin_user_id');
            $table->foreign('manager_user_id')->references('id')->on('users')->nullOnDelete();
        });

        DB::table('registration')
            ->whereNull('manager_address')
            ->update(['manager_address' => DB::raw('admin_address')]);
    }

    public function down(): void
    {
        Schema::table('registration', function (Blueprint $table): void {
            $table->dropForeign(['manager_user_id']);
            $table->dropColumn(['manager_address', 'manager_user_id']);
        });
    }
};
