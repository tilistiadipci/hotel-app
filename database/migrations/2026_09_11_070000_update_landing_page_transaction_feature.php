<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_CONTENT = '<article class="lp-feature"><span class="lp-icon">▣</span><h3>Multi-Hotel</h3><p>Pantau banyak hotel beserta admin, status, konfigurasi, dan aktivitasnya dari satu akun platform.</p></article>';

    private const NEW_CONTENT = '<article class="lp-feature"><span class="lp-icon">↗</span><h3>Pantau Transaksi</h3><p>Pantau transaksi dari seluruh hotel secara real-time dari mana saja melalui satu dashboard terpusat.</p></article>';

    public function up(): void
    {
        $this->replace(self::OLD_CONTENT, self::NEW_CONTENT);
    }

    public function down(): void
    {
        $this->replace(self::NEW_CONTENT, self::OLD_CONTENT);
    }

    private function replace(string $search, string $replacement): void
    {
        DB::table('landing_pages')
            ->where('html_content', 'like', '%'.$search.'%')
            ->get(['id', 'html_content'])
            ->each(function ($landingPage) use ($search, $replacement) {
                DB::table('landing_pages')->where('id', $landingPage->id)->update([
                    'html_content' => str_replace($search, $replacement, $landingPage->html_content),
                    'updated_at' => now(),
                ]);
            });
    }
};
