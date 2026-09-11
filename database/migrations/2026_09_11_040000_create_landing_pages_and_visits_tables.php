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
            'site_name' => config('app.name', 'Hotel Platform'),
            'meta_title' => config('app.name', 'Hotel Platform'),
            'meta_description' => 'Platform pengelolaan hotel, konten, tenant, dan player dalam satu tempat.',
            'meta_keywords' => 'hotel, hotel management, digital signage, hospitality',
            'html_content' => <<<'HTML'
<main style="min-height:100vh;display:grid;place-items:center;padding:32px;background:linear-gradient(135deg,#12233f,#315fa7);font-family:Arial,sans-serif;color:#fff;text-align:center">
    <section style="max-width:760px">
        <p style="text-transform:uppercase;letter-spacing:.18em;opacity:.75">Hotel Platform</p>
        <h1 style="font-size:clamp(40px,7vw,76px);margin:16px 0">Kelola pengalaman hotel dari satu platform.</h1>
        <p style="font-size:18px;line-height:1.7;opacity:.82">Atur tenant, player, media, dan layanan hotel dengan lebih terpusat.</p>
        <a href="{{LOGIN_URL}}" style="display:inline-block;margin-top:24px;padding:14px 24px;border-radius:10px;background:#fff;color:#244d8d;text-decoration:none;font-weight:bold">Login</a>
    </section>
</main>
HTML,
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
