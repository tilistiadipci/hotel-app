<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medias', function (Blueprint $table) {
            $table->dropIndex(['storage_path']);
        });

        Schema::table('medias', function (Blueprint $table) {
            $table->text('storage_path')->change();
        });
    }

    public function down(): void
    {
        Schema::table('medias', function (Blueprint $table) {
            $table->string('storage_path', 255)->change();
        });

        Schema::table('medias', function (Blueprint $table) {
            $table->index('storage_path');
        });
    }
};
