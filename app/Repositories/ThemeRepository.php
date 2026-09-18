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

    /**
     * Full theme catalog, not scoped to the hotel_theme assignment of any
     * single hotel. Used for the platform-admin view of Themes as master
     * data, as opposed to a hotel's own assigned themes.
     */
    public function getGlobalList()
    {
        return $this->globalQuery()
            ->with(['details', 'imageMedia'])
            ->orderBy('name')
            ->get()
            ->each(fn (Theme $theme) => $this->applyPivotDefault($theme));
    }

    public function findUidWithRelations(string $uuid): ?Theme
    {
        $theme = $this->assignedQuery()
            ->with(['details', 'imageMedia'])
            ->where('themes.uuid', $uuid)
            ->first();

        return $theme ? $this->applyHotelDefault($theme) : null;
    }

    public function findUidGlobalWithRelations(string $uuid): ?Theme
    {
        $theme = $this->globalQuery()
            ->with(['details', 'imageMedia'])
            ->where('themes.uuid', $uuid)
            ->first();

        return $theme ? $this->applyPivotDefault($theme) : null;
    }

    public function findUidGlobal(string $uuid): ?Theme
    {
        return Theme::query()->where('uuid', $uuid)->first();
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

        // Attach if this theme was never assigned to the hotel yet (e.g. a
        // platform admin defaulting a theme from the global catalog).
        Theme::query()->findOrFail($themeId)
            ->hotels()
            ->syncWithoutDetaching([$this->hotelId() => ['is_default' => true]]);
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

    private function globalQuery()
    {
        $hotelId = $this->hotelId();

        return Theme::query()->with(['hotels' => fn ($query) => $query->where('hotels.id', $hotelId)]);
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

    private function applyPivotDefault(Theme $theme): Theme
    {
        $theme->setAttribute('is_default', (string) (int) ($theme->hotels->first()?->pivot?->is_default ?? false));

        return $theme;
    }
}
