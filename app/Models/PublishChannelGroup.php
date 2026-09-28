<?php

namespace App\Models;

class PublishChannelGroup extends TenantModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(PublishChannelGroupItem::class)->orderBy('sort_order');
    }
}
