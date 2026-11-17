<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    protected $table = 'admins';

    protected $guarded = [];

    protected $hidden = ['password'];

    protected $casts = [
        'last_login_at' => 'datetime',
        'is_super' => 'integer',
        'status' => 'integer',
    ];

    public function isSuper(): bool
    {
        return (int) $this->is_super === 1;
    }
}
