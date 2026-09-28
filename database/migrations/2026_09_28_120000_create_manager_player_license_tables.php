<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_licenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_id');
            $table->foreignId('master_paket_id')->constrained('master_paket')->cascadeOnDelete();
            $table->unsignedInteger('quantity_players')->nullable();
            $table->unsignedInteger('max_users')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('manager_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['manager_id', 'master_paket_id', 'status']);
        });

        Schema::create('player_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_license_id')->constrained('manager_licenses')->cascadeOnDelete();
            $table->uuid('hotel_id');
            $table->unsignedBigInteger('player_id')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('status', 30)->default('active')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
            $table->foreign('player_id')->references('id')->on('players')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['manager_license_id', 'player_id'], 'player_license_unique_player');
            $table->index(['hotel_id', 'status']);
        });

        Schema::table('hotel_licenses', function (Blueprint $table) {
            $table->foreignId('manager_license_id')->nullable()->after('master_paket_id')
                ->constrained('manager_licenses')->nullOnDelete();
            $table->unsignedBigInteger('assigned_by_manager_id')->nullable()->after('manager_license_id');
            $table->foreign('assigned_by_manager_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hotel_licenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_license_id');
            $table->dropForeign(['assigned_by_manager_id']);
            $table->dropColumn('assigned_by_manager_id');
        });

        Schema::dropIfExists('player_licenses');
        Schema::dropIfExists('manager_licenses');
    }
};
