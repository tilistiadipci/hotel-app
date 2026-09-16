<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterKelurahanDesa extends Model
{
    protected $table = 'master_kelurahan_desa';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
