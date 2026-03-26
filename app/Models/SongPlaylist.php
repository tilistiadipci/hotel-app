<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SongPlaylist extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'sort_order',
        'is_active',
        'is_favorit',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_favorit' => 'boolean',
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
        $query->when($filters['search']['value'] ?? false, function ($builder, $search) {
            $builder->where('name', 'like', '%' . $search . '%');
        });
    }

    public function songs()
    {
        return $this->hasMany(Song::class, 'song_playlist_id');
    }
}
