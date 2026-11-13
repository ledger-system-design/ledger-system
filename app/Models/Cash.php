<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cash extends Model
{
    protected $table = 'cashes';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
        'addtime' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'memberid');
    }
}
