<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MovieCategory extends Model
{
    use HasFactory, SoftDeletes;

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

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search']['value'] ?? false, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
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
    }
}
