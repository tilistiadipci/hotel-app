<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('master_provinsi')) {
            Schema::create('master_provinsi', function (Blueprint $table): void {
                $table->string('id', 2)->primary();
                $table->string('nama', 150)->index();
            });
        }

        if (! Schema::hasTable('master_kabupaten_kota')) {
            Schema::create('master_kabupaten_kota', function (Blueprint $table): void {
                $table->string('id', 5)->primary();
                $table->string('provinsi_id', 2)->index();
                $table->string('nama', 180)->index();
                $table->enum('tipe', ['Kabupaten', 'Kota'])->nullable();
                $table->foreign('provinsi_id')->references('id')->on('master_provinsi')
                    ->cascadeOnUpdate()->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('master_kecamatan')) {
            Schema::create('master_kecamatan', function (Blueprint $table): void {
                $table->string('id', 8)->primary();
                $table->string('kabupaten_kota_id', 5)->index();
                $table->string('nama', 180)->index();
                $table->foreign('kabupaten_kota_id')->references('id')->on('master_kabupaten_kota')
                    ->cascadeOnUpdate()->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('master_kelurahan_desa')) {
            Schema::create('master_kelurahan_desa', function (Blueprint $table): void {
                $table->string('id', 13)->primary();
                $table->string('kecamatan_id', 8)->index();
                $table->string('nama', 220)->index();
                $table->string('kode_pos', 5)->nullable()->index();
                $table->foreign('kecamatan_id')->references('id')->on('master_kecamatan')
                    ->cascadeOnUpdate()->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn('hotels', 'adm4')) {
            Schema::table('hotels', function (Blueprint $table): void {
                $table->string('adm4', 13)->nullable()->after('address')->index();
                $table->foreign('adm4')->references('id')->on('master_kelurahan_desa')
                    ->cascadeOnUpdate()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('hotels', 'adm4')) {
            Schema::table('hotels', function (Blueprint $table): void {
                $table->dropForeign(['adm4']);
                $table->dropIndex(['adm4']);
                $table->dropColumn('adm4');
            });
        }

        // Tabel master mungkin sudah ada sebelum migration ini dijalankan
        // (diimpor dari database/wilayah_indonesia.sql), jadi jangan dihapus.
    }
};
