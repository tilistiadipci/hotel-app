<?php

namespace App\Repositories;

use App\Models\PlaceCategory;

class PlaceCategoryRepository extends BaseRepository
{
    public function __construct(PlaceCategory $category)
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
}
