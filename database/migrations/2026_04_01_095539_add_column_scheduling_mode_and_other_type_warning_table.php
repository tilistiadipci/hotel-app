<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('warnings', function (Blueprint $table) {
            $table->string('other_type')->after('priority')->nullable();
            $table->integer('scheduled')->after('other_type')->default(0); // 0 = now, 5 = in 5 minutes, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warnings', function (Blueprint $table) {
            $table->dropColumn('other_type');
            $table->dropColumn('scheduled');
        });
    }
};
