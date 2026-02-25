<?php

namespace App\Repositories;

use App\Models\MovieCategory;

class MovieCategoryRepository extends BaseRepository
{
    public function __construct(MovieCategory $category)
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

    public function findBySlug(string $slug)
    {
        return $this->model->where('slug', $slug)->first();
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
