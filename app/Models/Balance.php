<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    protected $table = 'balances';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
        'prebalance' => 'float',
        'balance' => 'float',
        'addtime' => 'datetime',
    ];
}
