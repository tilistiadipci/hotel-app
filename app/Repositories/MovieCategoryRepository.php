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
}
