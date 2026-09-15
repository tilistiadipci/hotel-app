<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration', function (Blueprint $table) {
            $table->text('hotel_address')->nullable()->after('hotel_name');
            $table->string('username')->nullable()->after('person_in_charge')->index();
            $table->string('gender', 10)->nullable()->after('whatsapp');
            $table->string('admin_address', 255)->nullable()->after('gender');
            $table->string('password')->nullable()->after('admin_address');
            $table->uuid('hotel_id')->nullable()->after('reviewed_at');
            $table->foreignId('admin_user_id')->nullable()->after('hotel_id');

            $table->foreign('hotel_id')->references('id')->on('hotels')->nullOnDelete();
            $table->foreign('admin_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('registration', function (Blueprint $table) {
            $table->dropForeign(['hotel_id']);
            $table->dropForeign(['admin_user_id']);
            $table->dropColumn(['hotel_address', 'username', 'gender', 'admin_address', 'password', 'hotel_id', 'admin_user_id']);
        });
    }
};
