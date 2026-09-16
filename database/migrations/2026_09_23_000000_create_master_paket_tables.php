<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_paket', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama', 120);
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('durasi_hari')->nullable();
            $table->unsignedInteger('maksimal_player')->nullable();
            $table->unsignedInteger('maksimal_user')->nullable();
            $table->boolean('paket_default_registrasi')->default(false)->index();
            $table->boolean('aktif')->default(true)->index();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('master_paket_tv_channel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_paket_id')->constrained('master_paket')->cascadeOnDelete();
            $table->foreignId('tv_channel_id')->constrained('tv_channels')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['master_paket_id', 'tv_channel_id'], 'paket_tv_channel_unique');
        });

        Schema::table('hotel_licenses', function (Blueprint $table) {
            $table->foreignId('master_paket_id')->nullable()->after('plan_code')
                ->constrained('master_paket')->nullOnDelete();
        });

        $now = now();
        $paket = [
            ['kode' => 'trial', 'nama' => 'Trial', 'deskripsi' => 'Paket uji coba untuk hotel yang baru mendaftar.', 'durasi_hari' => 14, 'maksimal_player' => 3, 'maksimal_user' => 3, 'paket_default_registrasi' => true, 'aktif' => true, 'urutan' => 1],
            ['kode' => 'standard', 'nama' => 'Standard', 'deskripsi' => 'Paket untuk operasional hotel reguler.', 'durasi_hari' => null, 'maksimal_player' => 50, 'maksimal_user' => 10, 'paket_default_registrasi' => false, 'aktif' => true, 'urutan' => 2],
            ['kode' => 'premium', 'nama' => 'Premium', 'deskripsi' => 'Paket untuk hotel dengan kebutuhan player dan user lebih besar.', 'durasi_hari' => null, 'maksimal_player' => 200, 'maksimal_user' => 50, 'paket_default_registrasi' => false, 'aktif' => true, 'urutan' => 3],
            ['kode' => 'custom', 'nama' => 'Custom', 'deskripsi' => 'Paket khusus yang konfigurasinya ditentukan superadmin.', 'durasi_hari' => null, 'maksimal_player' => null, 'maksimal_user' => null, 'paket_default_registrasi' => false, 'aktif' => true, 'urutan' => 4],
        ];

        foreach ($paket as $row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            DB::table('master_paket')->insert($row);
        }

        $trialId = DB::table('master_paket')->where('kode', 'trial')->value('id');
        $masterHotelId = DB::table('hotels')->where('is_system', true)->value('id');
        if ($trialId && $masterHotelId) {
            $channelIds = DB::table('tv_channels')
                ->where('hotel_id', $masterHotelId)->where('is_active', true)->whereNull('deleted_at')->pluck('id');
            foreach ($channelIds as $channelId) {
                DB::table('master_paket_tv_channel')->insert([
                    'master_paket_id' => $trialId,
                    'tv_channel_id' => $channelId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        DB::table('master_paket')->get(['id', 'kode'])->each(function ($row): void {
            DB::table('hotel_licenses')->where('plan_code', $row->kode)->update(['master_paket_id' => $row->id]);
        });
    }

    public function down(): void
    {
        Schema::table('hotel_licenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('master_paket_id');
        });
        Schema::dropIfExists('master_paket_tv_channel');
        Schema::dropIfExists('master_paket');
    }
};
