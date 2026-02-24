<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Song extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'artist_id',
        'album_id',
        'title',
        'url_stream',
        'duration',
        'cover_image',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($song) {
            if (empty($song->uuid)) {
                $song->uuid = (string) Str::uuid();
            }
        });
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search']['value'] ?? false, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhereHas('artist', fn ($qa) => $qa->where('name', 'like', '%' . $search . '%'))
                  ->orWhereHas('album', fn ($qb) => $qb->where('title', 'like', '%' . $search . '%'));
            });
        });

        $filter = $filters['filters'] ?? [];

        $query->when($filter['artist_id'] ?? false, fn ($q, $artistId) => $q->where('artist_id', $artistId));
        $query->when($filter['album_id'] ?? false, fn ($q, $albumId) => $q->where('album_id', $albumId));
        $query->when(isset($filter['is_active']), fn ($q) => $q->where('is_active', $filter['is_active']));
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function album()
    {
        return $this->belongsTo(Album::class);
    }
}
