<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Album extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'artist_id',
        'title',
        'cover_image',
        'release_date',
    ];

    protected $casts = [
        'release_date' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($album) {
            if (empty($album->uuid)) {
                $album->uuid = (string) Str::uuid();
            }
        });
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search']['value'] ?? false, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhereHas('artist', function ($qa) use ($search) {
                      $qa->where('name', 'like', '%' . $search . '%');
                  });
            });
        });

        $filter = $filters['filters'] ?? [];

        $query->when($filter['artist_id'] ?? false, fn ($q, $artistId) => $q->where('artist_id', $artistId));
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function songs()
    {
        return $this->hasMany(Song::class);
    }
}
