<?php

namespace App\Models;

class PublishContentGroup extends TenantModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(PublishContentGroupItem::class);
    }
}
