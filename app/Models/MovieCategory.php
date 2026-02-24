<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MovieCategory extends Model
{
    use HasFactory;

    protected $table = 'movies_categories';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($category) {
            if (empty($category->uuid)) {
                $category->uuid = (string) Str::uuid();
            }
        });
    }

    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'movie_category_relations', 'category_id', 'movie_id')
            ->withTimestamps();
    }
}
