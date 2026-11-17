<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'addresses';

    protected $guarded = [];

    protected $casts = [
        'addtime' => 'datetime',
        'isdefault' => 'integer',
    ];

    public $timestamps = true;
}
