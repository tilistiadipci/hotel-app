<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Theme extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function details()
    {
        return $this->hasMany(ThemeDetail::class, 'theme_id');
    }

    public function imageMedia()
    {
        return $this->belongsTo(Media::class, 'image_id');
    }
}
