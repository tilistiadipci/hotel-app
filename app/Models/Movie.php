<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Movie extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'thumbnail',
        'banner_image',
        'url_stream',
        'duration',
        'release_date',
        'rating',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'release_date' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($movie) {
            if (empty($movie->uuid)) {
                $movie->uuid = (string) Str::uuid();
            }
        });
    }

    public function categories()
    {
        return $this->belongsToMany(MovieCategory::class, 'movie_category_relations', 'movie_id', 'category_id')
            ->withTimestamps();
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search']['value'] ?? false, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        });

        $filter = $filters['filters'] ?? [];

        $query->when(array_key_exists('status', $filter), function ($q) use ($filter) {
            $status = $filter['status'];
            if ($status === '1' || $status === 1) {
                $q->where('is_active', 1);
            } elseif ($status === '0' || $status === 0) {
                $q->where('is_active', 0);
            }
        });

        $query->when($filter['category_id'] ?? false, function ($q, $categoryId) {
            $q->whereHas('categories', function ($qc) use ($categoryId) {
                $qc->where('movies_categories.id', $categoryId);
            });
        });
    }
}
