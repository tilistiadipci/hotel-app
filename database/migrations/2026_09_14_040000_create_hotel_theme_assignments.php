<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_theme', function (Blueprint $table) {
            $table->id();
            $table->uuid('hotel_id');
            $table->foreignId('theme_id');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
            $table->foreign('theme_id')->references('id')->on('themes')->cascadeOnDelete();
            $table->unique(['hotel_id', 'theme_id'], 'hotel_theme_unique');
            $table->index(['hotel_id', 'is_default'], 'hotel_theme_default_index');
        });

        $now = now();
        $themes = DB::table('themes')->whereNull('deleted_at')->get(['id', 'hotel_id', 'is_default']);

        foreach ($themes as $theme) {
            if (! $theme->hotel_id) {
                continue;
            }

            DB::table('hotel_theme')->updateOrInsert(
                ['hotel_id' => $theme->hotel_id, 'theme_id' => $theme->id],
                ['is_default' => (string) $theme->is_default === '1', 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $defaultThemeId = DB::table('themes')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('id', 1)->orWhere('name', 'Default Theme');
            })
            ->orderByRaw('CASE WHEN id = 1 THEN 0 ELSE 1 END')
            ->value('id');

        if (! $defaultThemeId) {
            return;
        }

        foreach (DB::table('hotels')->whereNull('deleted_at')->pluck('id') as $hotelId) {
            $hasDefault = DB::table('hotel_theme')->where('hotel_id', $hotelId)->where('is_default', true)->exists();

            DB::table('hotel_theme')->updateOrInsert(
                ['hotel_id' => $hotelId, 'theme_id' => $defaultThemeId],
                [
                    'is_default' => $hasDefault
                        ? DB::table('hotel_theme')->where('hotel_id', $hotelId)->where('theme_id', $defaultThemeId)->value('is_default') ?? false
                        : true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_theme');
    }
};
