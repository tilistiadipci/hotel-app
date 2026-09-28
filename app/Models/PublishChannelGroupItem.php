<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublishChannelGroupItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(PublishChannelGroup::class, 'publish_channel_group_id');
    }

    public function tvChannel()
    {
        return $this->belongsTo(TvChannel::class);
    }
}
