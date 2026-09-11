<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function oldTemplate(): string
    {
        return <<<'HTML'
<main style="min-height:100vh;display:grid;place-items:center;padding:32px;background:linear-gradient(135deg,#12233f,#315fa7);font-family:Arial,sans-serif;color:#fff;text-align:center">
    <section style="max-width:760px">
        <p style="text-transform:uppercase;letter-spacing:.18em;opacity:.75">Hotel Platform</p>
        <h1 style="font-size:clamp(40px,7vw,76px);margin:16px 0">Kelola pengalaman hotel dari satu platform.</h1>
        <p style="font-size:18px;line-height:1.7;opacity:.82">Atur tenant, player, media, dan layanan hotel dengan lebih terpusat.</p>
        <a href="{{LOGIN_URL}}" style="display:inline-block;margin-top:24px;padding:14px 24px;border-radius:10px;background:#fff;color:#244d8d;text-decoration:none;font-weight:bold">Login</a>
    </section>
</main>
HTML;
    }

    public function up(): void
    {
        DB::table('landing_pages')
            ->where('html_content', $this->oldTemplate())
            ->update([
                'site_name' => 'HotelSpace',
                'meta_title' => 'HotelSpace - Platform Manajemen Hotel Terintegrasi',
                'meta_description' => 'Kelola hotel, player TV, tenant, media, lisensi, dan pengalaman tamu dari satu platform terintegrasi.',
                'meta_keywords' => 'hotel management system, CMS hotel, hotel TV, digital signage hotel, hospitality platform',
                'html_content' => file_get_contents(resource_path('landing/default.html')),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('landing_pages')
            ->where('html_content', file_get_contents(resource_path('landing/default.html')))
            ->update([
                'html_content' => $this->oldTemplate(),
                'updated_at' => now(),
            ]);
    }
};
