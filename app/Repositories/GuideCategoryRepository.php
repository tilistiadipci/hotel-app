<?php

namespace App\Repositories;

use App\Models\GuideCategory;

class GuideCategoryRepository extends BaseRepository
{
    public function __construct(GuideCategory $category)
    {
        parent::__construct($category);
    }

    public function create(array $attributes)
    {
        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        // Table does not track created_by/updated_by, so use plain create.
        return $this->model->create($attributes);
    }

    public function update($id, array $attributes)
    {
        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        $record = $this->find($id);

        if ($record) {
            $record->update($attributes);
            return $record;
        }

        return false;
    }

    public function deleteById($id)
    {
        $record = $this->find($id);
        return $record ? $record->delete() : false;
    }

    public function findBySlug(string $slug)
    {
        return $this->model->where('slug', $slug)->first();
    }
}
