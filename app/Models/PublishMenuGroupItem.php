<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublishMenuGroupItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(PublishMenuGroup::class, 'publish_menu_group_id');
    }
}
