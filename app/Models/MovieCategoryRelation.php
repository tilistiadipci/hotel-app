<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovieCategoryRelation extends Model
{
    use HasFactory;

    protected $table = 'movie_category_relations';

    protected $fillable = [
        'movie_id',
        'category_id',
    ];

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    public function category()
    {
        return $this->belongsTo(MovieCategory::class, 'category_id');
    }
}
