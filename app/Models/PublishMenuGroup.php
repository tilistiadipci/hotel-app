<?php

namespace App\Models;

class PublishMenuGroup extends TenantModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(PublishMenuGroupItem::class)->orderBy('sort_order');
    }
}
