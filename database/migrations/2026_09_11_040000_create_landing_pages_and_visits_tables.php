<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->default('Hotel Platform');
            $table->string('meta_title');
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('logo_path')->nullable();
            $table->longText('html_content');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('landing_page_visits', function (Blueprint $table) {
            $table->id();
            $table->ipAddress('ip_address')->index();
            $table->text('user_agent')->nullable();
            $table->text('referrer')->nullable();
            $table->timestamp('visited_at')->index();
            $table->timestamps();
        });

        DB::table('landing_pages')->insert([
            'site_name' => 'HotelSpace',
            'meta_title' => 'HotelSpace - Platform Manajemen Hotel Terintegrasi',
            'meta_description' => 'Kelola hotel, player TV, tenant, media, lisensi, dan pengalaman tamu dari satu platform terintegrasi.',
            'meta_keywords' => 'hotel management system, CMS hotel, hotel TV, digital signage hotel, hospitality platform',
            'html_content' => file_get_contents(resource_path('landing/default.html')),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_page_visits');
        Schema::dropIfExists('landing_pages');
    }
};
