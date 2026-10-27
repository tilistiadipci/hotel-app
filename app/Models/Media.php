<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'medias';

    protected $fillable = [
        'uuid',
        'name',
        'original_filename',
        'type',
        'extension',
        'storage_path',
        'mime_type',
        'size',
        'duration',
        'width',
        'height',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function booted()
    {
        static::creating(function ($media) {
            if (empty($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }
        });
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search']['value'] ?? false, function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', '%' . $search . '%')
                    ->orWhere('original_filename', 'like', '%' . $search . '%')
                    ->orWhere('storage_path', 'like', '%' . $search . '%');
            });
        });

        $filter = $filters['filters'] ?? [];
        $query->when($filter['type'] ?? false, function ($q, $type) {
            $q->where('type', $type);
        });
    }
}
