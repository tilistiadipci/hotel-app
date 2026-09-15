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
            $table->string('phone', 30)->nullable()->after('email');
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->text('address')->nullable()->after('name');
        });

        Schema::create('hotel_manager', function (Blueprint $table) {
            $table->id();
            $table->uuid('hotel_id');
            $table->unsignedBigInteger('manager_id');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
            $table->foreign('manager_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['hotel_id', 'manager_id']);
            $table->index(['manager_id', 'is_active']);
        });

        if (Schema::hasTable('user_profiles')) {
            DB::table('users')->whereNull('phone')->update([
                'phone' => DB::raw('(SELECT user_profiles.phone FROM user_profiles WHERE user_profiles.user_id = users.id AND user_profiles.deleted_at IS NULL LIMIT 1)'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_manager');

        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn('address');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
