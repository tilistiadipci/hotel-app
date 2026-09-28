<?php

namespace App\Services;

use App\Models\GuideItem;
use App\Models\MenuTenant;
use App\Models\Movie;
use App\Models\Place;
use App\Models\Player;
use App\Models\PlayerContentScope;
use App\Models\Song;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class PlayerCatalogManager
{
    /** @return Collection<int, mixed> */
    public function effective(Player $player, string $type): Collection
    {
        $query = $this->queryFor($type)->where('is_active', true);
        $scope = $player->contentScopes()->with('items')->where('content_type', $type)->first();

        if (! $scope || $scope->mode === 'all') {
            return $query->get();
        }

        $ids = $scope->items->pluck('content_id')->map(fn ($id) => (int) $id)->all();

        if (empty($ids)) {
            return collect();
        }

        return $query->whereIn('id', $ids)->get();
    }

    private function queryFor(string $type): Builder
    {
        return match ($type) {
            'menu_tenant' => MenuTenant::query(),
            'movie' => Movie::query(),
            'song' => Song::query(),
            'guide' => GuideItem::query(),
            'place' => Place::query(),
            default => throw new InvalidArgumentException("Unsupported player catalog type [{$type}]."),
        };
    }
}
