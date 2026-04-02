<?php

namespace App\Repositories;

use App\Models\MenuCategory;

class MenuCategoryRepository extends BaseRepository
{
    public function __construct(MenuCategory $category)
    {
        parent::__construct($category);
    }

    public function create(array $attributes)
    {
        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        return $this->model->create($attributes);
    }

    public function findBySlug(string $slug, ?int $tenantId = null)
    {
        return $this->model
            ->when($tenantId, fn ($query) => $query->where('menu_tenant_id', $tenantId))
            ->where('slug', $slug)
            ->whereNull('deleted_at')
            ->first();
    }

    public function bulkDeleteByUid(array $uids, $fieldName = null, $destroyImage = false)
    {
        if (empty($uids)) {
            return 0;
        }

        return $this->model->whereIn('uuid', $uids)->update([
            'deleted_by' => auth()->id(),
            'deleted_at' => now(),
        ]);
    }
}
