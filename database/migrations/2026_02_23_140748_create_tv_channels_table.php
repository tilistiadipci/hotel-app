<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_channels', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 150); // nama channel yang ditampilkan di kartu/list (mis. â€œCNN Internationalâ€).
            $table->string('slug', 180)->unique(); // versi URL/unique identifier ramah string; dipakai untuk routing/query.
            $table->foreignId('image_id')->nullable()->constrained('medias')->nullOnDelete(); // relasi ke media image (logo)
            $table->enum('type', ['digital', 'streaming']); //  jenis sumber siaran; membedakan tab â€œDigital TVâ€ vs â€œStreaming TVâ€.
            $table->enum('region', ['national', 'international']); //  cakupan siaran; â€œnationalâ€ atau â€œinternationalâ€ untuk filter kategori.
            $table->string('stream_url', 255)->nullable(); // URL stream (HLS/DASH, dll.) untuk channel streaming; bisa kosong untuk digital channel
            $table->string('frequency', 100)->nullable(); // frekuensi / mux info untuk channel digital; tidak dipakai di streaming.
            $table->string('quality', 20)->nullable(); // informasi kualitas siaran, mis. â€œHDâ€ / â€œSDâ€.
            $table->unsignedSmallInteger('sort_order')->default(0); // urutan tampilan manual di grid/list
            $table->boolean('is_active')->default(true); // status aktif/tidak aktif untuk kontrol visibilitas channel di aplikasi

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'region', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_channels');
    }
};

