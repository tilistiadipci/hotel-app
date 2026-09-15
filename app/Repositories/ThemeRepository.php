<?php

namespace App\Repositories;

use App\Models\Hotel;
use App\Models\Theme;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

class ThemeRepository extends BaseRepository
{
    public function __construct(Theme $theme)
    {
        parent::__construct($theme);
    }

    public function getList()
    {
        return $this->assignedQuery()
            ->with(['details', 'imageMedia'])
            ->orderBy('name')
            ->get()
            ->each(fn (Theme $theme) => $this->applyHotelDefault($theme));
    }

    public function findUidWithRelations(string $uuid): ?Theme
    {
        $theme = $this->assignedQuery()
            ->with(['details', 'imageMedia'])
            ->where('themes.uuid', $uuid)
            ->first();

        return $theme ? $this->applyHotelDefault($theme) : null;
    }

    public function resetDefaultExcept(int $themeId): void
    {
        DB::table('hotel_theme')
            ->where('hotel_id', $this->hotelId())
            ->where('theme_id', '!=', $themeId)
            ->update([
                'is_default' => false,
                'updated_at' => now(),
            ]);
    }

    public function setDefault(int $themeId): void
    {
        $this->resetDefaultExcept($themeId);

        DB::table('hotel_theme')
            ->where('hotel_id', $this->hotelId())
            ->where('theme_id', $themeId)
            ->update(['is_default' => true, 'updated_at' => now()]);
    }

    public function updateDefaultStatus(int $themeId, bool $isDefault): void
    {
        if ($isDefault) {
            $this->setDefault($themeId);

            return;
        }

        DB::table('hotel_theme')
            ->where('hotel_id', $this->hotelId())
            ->where('theme_id', $themeId)
            ->update(['is_default' => false, 'updated_at' => now()]);
    }

    public function find($id)
    {
        $theme = $this->assignedQuery()->where('themes.id', $id)->first();

        return $theme ? $this->applyHotelDefault($theme) : null;
    }

    public function findUid($uid)
    {
        $theme = $this->assignedQuery()->where('themes.uuid', $uid)->first();

        return $theme ? $this->applyHotelDefault($theme) : null;
    }

    private function assignedQuery()
    {
        return $this->hotel()->themes();
    }

    private function hotel(): Hotel
    {
        return Hotel::query()->findOrFail($this->hotelId());
    }

    private function hotelId(): string
    {
        return app(TenantContext::class)->id()
            ?? throw new \LogicException('Hotel context is required to manage themes.');
    }

    private function applyHotelDefault(Theme $theme): Theme
    {
        $theme->setAttribute('is_default', (string) (int) ($theme->pivot?->is_default ?? false));

        return $theme;
    }
}
