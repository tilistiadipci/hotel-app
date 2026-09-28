<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_tvs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('hotel_id');
            $table->string('name', 150); // nama/label TV, mis. "TV LED Kamar Superior"
            $table->string('brand', 100); // merk/jenis TV, mis. "Samsung", "LG", "Sony"
            $table->string('size', 20); // ukuran layar, mis. "32 Inch", "43 Inch"
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
        });

        Schema::table('players', function (Blueprint $table) {
            $table->unsignedBigInteger('master_tv_id')->nullable()->after('player_group_id');
            $table->foreign('master_tv_id')->references('id')->on('master_tvs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropForeign(['master_tv_id']);
            $table->dropColumn('master_tv_id');
        });

        Schema::dropIfExists('master_tvs');
    }
};
