<?php

namespace App\Repositories;

use App\Models\Theme;

class ThemeRepository extends BaseRepository
{
    public function __construct(Theme $theme)
    {
        parent::__construct($theme);
    }

    public function getList()
    {
        return $this->query()
            ->with(['details', 'imageMedia'])
            ->orderBy('name')
            ->get();
    }

    public function findUidWithRelations(string $uuid): ?Theme
    {
        return $this->query()
            ->with(['details', 'imageMedia'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function resetDefaultExcept(int $themeId): void
    {
        $this->query()
            ->where('id', '!=', $themeId)
            ->update([
                'is_default' => '0',
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);
    }
}
