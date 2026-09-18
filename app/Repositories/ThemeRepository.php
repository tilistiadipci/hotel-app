<?php

namespace App\Repositories;

use App\Models\Hotel;
use App\Models\Theme;
use App\Models\ThemeDetail;
use App\Tenancy\TenantContext;
use Illuminate\Support\Collection;
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

    /**
     * Raw key => value theme_details set by the platform admin on the
     * Master hotel, for the given theme. Used as a preview-only fallback so
     * a hotel that hasn't customized a field yet still sees the platform
     * default instead of a blank preview - a hotel's own saved value always
     * wins once it sets one. Empty when the current context already is the
     * Master hotel (nothing to fall back to).
     */
    public function getMasterDetailMap(int $themeId): Collection
    {
        $masterId = Hotel::masterId();

        if (! $masterId || $masterId === $this->hotelId()) {
            return collect();
        }

        return ThemeDetail::query()
            ->withoutGlobalScope('hotel')
            ->where('hotel_id', $masterId)
            ->where('theme_id', $themeId)
            ->pluck('value', 'key');
    }

    /**
     * The Master hotel's own code, needed to resolve its media through the
     * media API (which serves files from the hotel's own media_root), since
     * the current tenant context is a different hotel when this is used.
     */
    public function getMasterHotelCode(): ?string
    {
        $masterId = Hotel::masterId();

        if (! $masterId || $masterId === $this->hotelId()) {
            return null;
        }

        return Hotel::query()->whereKey($masterId)->value('code');
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
