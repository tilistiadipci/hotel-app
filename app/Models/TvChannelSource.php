<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TvChannelSource extends Model
{
    protected $fillable = [
        'tv_channel_id',
        'label',
        'stream_url',
        'entry_text',
        'source_hash',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function channel()
    {
        return $this->belongsTo(TvChannel::class, 'tv_channel_id');
    }
}
